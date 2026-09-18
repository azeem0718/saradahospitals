#!/usr/bin/env python3
"""
Today's Nifty bias: UP / DOWN / SIDEWAYS, an approximate range, and a 0-100 score.

How it works
------------
1. Pull ~10 years of daily data for Nifty 50 and the overnight factors
   (US close, VIX, dollar index, crude, gold, copper, Nikkei, USD/INR).
2. Fit two small linear regressions on that history:
     opening gap   ~ previous-session factor moves
     close-to-close ~ previous-session factor moves
3. Plug in the latest factor moves to get an expected gap and expected
   full-day move for the next Nifty session.
4. Score = probability that Nifty closes above the previous close,
   from the expected move and the historical spread of the model's errors.
     score >= 57  -> UP        score <= 43 -> DOWN        otherwise -> SIDEWAYS
5. Range = expected close +/- half of the 14-day average true range.

Honesty note: the historical edge is small. Scores rarely leave the 40-60
band, and a 60 means "6 days in 10", not a certainty. The gap forecast is
far more reliable than the close forecast.

Usage:  python3 nifty_daily_bias.py [--years 10] [--json bias.json] [--html today.html]
Needs:  pandas, numpy, requests
"""
import argparse, json, math, os, sys, time
from datetime import datetime, timedelta, timezone
import numpy as np
import pandas as pd
import requests

IST = timezone(timedelta(hours=5, minutes=30))

TICKERS = {
    "nifty":  ("^NSEI",     "Nifty 50"),
    "spx":    ("^GSPC",     "S&P 500"),
    "vix":    ("^VIX",      "VIX"),
    "dxy":    ("DX-Y.NYB",  "Dollar index"),
    "crude":  ("CL=F",      "WTI crude"),
    "gold":   ("GC=F",      "Gold"),
    "copper": ("HG=F",      "Copper"),
    "nikkei": ("^N225",     "Nikkei 225"),
    "usdinr": ("USDINR=X",  "USD/INR"),
}
FACTORS = [k for k in TICKERS if k != "nifty"]
# Sessions that close after Nifty's 15:30 IST close (US markets close ~01:30-02:30 IST next morning).
# Their bar dated D (by IST open time) is fresh information for Nifty's session D+1.
CLOSES_AFTER_INDIA = {"spx", "vix", "dxy", "crude", "gold", "copper"}
HDRS = {"User-Agent": "Mozilla/5.0"}


def fetch(symbol, years, cache_dir=".cache"):
    os.makedirs(cache_dir, exist_ok=True)
    cache = os.path.join(cache_dir, f"{symbol.replace('^','_').replace('=','_')}_{years}y_ohlc.csv")
    if os.path.exists(cache) and time.time() - os.path.getmtime(cache) < 3 * 3600:
        return pd.read_csv(cache, index_col=0, parse_dates=True)
    url = f"https://query2.finance.yahoo.com/v8/finance/chart/{requests.utils.quote(symbol)}"
    for attempt in range(4):
        r = requests.get(url, params={"range": f"{years}y", "interval": "1d"}, headers=HDRS, timeout=30)
        if r.status_code == 200:
            break
        time.sleep(2 * (attempt + 1))
    r.raise_for_status()
    res = r.json()["chart"]["result"][0]
    q = res["indicators"]["quote"][0]
    df = pd.DataFrame({"open": q["open"], "high": q["high"], "low": q["low"], "close": q["close"]},
                      index=pd.to_datetime(res["timestamp"], unit="s", utc=True))
    df.index = df.index.tz_convert("Asia/Kolkata").normalize().tz_localize(None)
    df = df[~df.index.duplicated(keep="last")].dropna()
    df.to_csv(cache)
    time.sleep(1.5)
    return df


def align_factors(nifty_dates, factor_rets):
    """For each Nifty date, the last factor close-to-close move that finished before it."""
    base = pd.DataFrame({"date": nifty_dates}).sort_values("date")
    out = base.copy()
    for name, ret in factor_rets.items():
        f = ret.reset_index()
        f.columns = ["fdate", name]
        m = pd.merge_asof(base, f.sort_values("fdate"), left_on="date", right_on="fdate",
                          direction="backward", allow_exact_matches=False)
        stale = (m["date"] - m["fdate"]).dt.days > 4
        m.loc[stale, name] = np.nan
        out[name] = m[name].values
    return out.set_index("date")


def ols(X, y):
    Xb = np.column_stack([np.ones(len(X)), X])
    beta, *_ = np.linalg.lstsq(Xb, y, rcond=None)
    resid = y - Xb @ beta
    return beta, float(resid.std(ddof=Xb.shape[1]))


def predict(beta, x):
    return float(beta[0] + np.dot(beta[1:], x))


def norm_cdf(z):
    return 0.5 * (1 + math.erf(z / math.sqrt(2)))


def next_trading_day(d):
    d = d + timedelta(days=1)
    while d.weekday() >= 5:
        d += timedelta(days=1)
    return d


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--years", type=int, default=10)
    ap.add_argument("--json", default=None)
    ap.add_argument("--html", default=None)
    args = ap.parse_args()

    data = {}
    for name, (sym, label) in TICKERS.items():
        try:
            data[name] = fetch(sym, args.years)
            print(f"fetched {label:13s} {len(data[name])} rows, last {data[name].index[-1].date()}", file=sys.stderr)
        except Exception as e:
            print(f"skip {label}: {e}", file=sys.stderr)
            if name == "nifty":
                sys.exit(1)
    factors = [f for f in FACTORS if f in data]

    now_ist = datetime.now(IST)
    n = data["nifty"].copy()
    # If Yahoo already shows today's bar and the session is still open, drop it: it is incomplete.
    if n.index[-1].date() == now_ist.date() and now_ist.time() < datetime.strptime("15:45", "%H:%M").time():
        n = n.iloc[:-1]
    last_date = n.index[-1]
    prev_close = float(n["close"].iloc[-1])
    target = next_trading_day(last_date.to_pydatetime())

    # --- history for the regressions
    hist = pd.DataFrame({
        "gap": n["open"] / n["close"].shift(1) - 1,
        "c2c": n["close"] / n["close"].shift(1) - 1,
    }).dropna()
    rets = {f: data[f]["close"].pct_change().dropna() for f in factors}
    X = align_factors(hist.index, rets).dropna()
    hist = hist.loc[X.index]
    # standardise factor moves so coefficients are comparable; cap outliers at 4 sd
    mu, sd = X.mean(), X.std()
    Z = ((X - mu) / sd).clip(-4, 4)
    beta_gap, s_gap = ols(Z.values, hist["gap"].values)
    beta_c2c, s_c2c = ols(Z.values, hist["c2c"].values)

    # --- latest factor moves (must have finished before the target session)
    latest = {}
    z_now = []
    today = pd.Timestamp(now_ist.date())
    for f in factors:
        r = rets[f]
        r = r[r.index < pd.Timestamp(target)]
        # a bar dated today is still in progress (US and FX sessions run past IST midnight;
        # Nikkei is complete after ~11:30 IST)
        nikkei_done = f == "nikkei" and now_ist.hour >= 12
        if not nikkei_done:
            r = r[r.index < today]
        move = float(r.iloc[-1]); fdate = r.index[-1]
        z = float(np.clip((move - mu[f]) / sd[f], -4, 4))
        # already priced in if that factor session finished before Nifty's last session closed
        priced_in = fdate < last_date if f in CLOSES_AFTER_INDIA else fdate <= last_date
        latest[f] = {"label": TICKERS[f][1], "move_pct": round(move * 100, 2), "as_of": str(fdate.date()),
                     "already_priced_in": bool(priced_in),
                     "contribution_bp": round(beta_c2c[1 + factors.index(f)] * z * 1e4, 1)}
        z_now.append(z)
    z_now = np.array(z_now)

    gap_hat = predict(beta_gap, z_now)
    c2c_hat = predict(beta_c2c, z_now)
    p_up = norm_cdf(c2c_hat / s_c2c)
    p_gap_up = norm_cdf(gap_hat / s_gap)
    score = round(p_up * 100)
    bias = "UP" if score >= 57 else "DOWN" if score <= 43 else "SIDEWAYS"
    if abs(c2c_hat) < 0.001:  # expected move under 0.1%: no direction worth trading
        bias = "SIDEWAYS"

    # --- range: 14-day ATR around the expected close
    tr = pd.concat([n["high"] - n["low"],
                    (n["high"] - n["close"].shift(1)).abs(),
                    (n["low"] - n["close"].shift(1)).abs()], axis=1).max(axis=1)
    atr14 = float(tr.rolling(14).mean().iloc[-1])
    exp_open = prev_close * (1 + gap_hat)
    exp_close = prev_close * (1 + c2c_hat)
    lo, hi = exp_close - atr14 / 2, exp_close + atr14 / 2
    # wider band: 1 sd of the close model's error
    lo1, hi1 = prev_close * (1 + c2c_hat - s_c2c), prev_close * (1 + c2c_hat + s_c2c)

    # --- CPR (central pivot range) and floor pivots from the last completed session
    H, L, C = float(n["high"].iloc[-1]), float(n["low"].iloc[-1]), prev_close
    piv = (H + L + C) / 3
    bc = (H + L) / 2
    tc = 2 * piv - bc
    tc, bc = max(tc, bc), min(tc, bc)
    cpr_width = (tc - bc) / piv * 100
    r1, s1 = 2 * piv - L, 2 * piv - H
    r2, s2 = piv + (H - L), piv - (H - L)
    hist_w = ((2 * (n["high"] + n["low"] + n["close"]) / 3 - (n["high"] + n["low"]) / 2)
              - (n["high"] + n["low"]) / 2).abs() / ((n["high"] + n["low"] + n["close"]) / 3) * 100
    trailing = hist_w.iloc[-251:-1]
    cpr_pctile = float((trailing < cpr_width).mean() * 100)
    cpr_label = "narrow" if cpr_pctile < 33 else "wide" if cpr_pctile > 67 else "average"
    if exp_open > tc:
        open_vs_cpr = "above the CPR"
    elif exp_open < bc:
        open_vs_cpr = "below the CPR"
    else:
        open_vs_cpr = "inside the CPR"
    cpr = {"tc": round(tc, 0), "pivot": round(piv, 0), "bc": round(bc, 0),
           "width_pct": round(cpr_width, 3), "width_percentile_1y": round(cpr_pctile, 0),
           "label": cpr_label, "expected_open_vs_cpr": open_vs_cpr,
           "r1": round(r1, 0), "r2": round(r2, 0), "s1": round(s1, 0), "s2": round(s2, 0),
           "prev_high": round(H, 0), "prev_low": round(L, 0)}

    stale = [latest[f]["label"] for f in factors if latest[f]["already_priced_in"]]

    out = {
        "computed_at_ist": now_ist.strftime("%Y-%m-%d %H:%M"),
        "for_session": str(target.date()),
        "last_completed_session": str(last_date.date()),
        "prev_close": round(prev_close, 2),
        "bias": bias, "score": score,
        "prob_close_up_pct": round(p_up * 100, 1),
        "prob_gap_up_pct": round(p_gap_up * 100, 1),
        "expected_open": round(exp_open, 0), "expected_gap_pct": round(gap_hat * 100, 2),
        "expected_close": round(exp_close, 0), "expected_move_pct": round(c2c_hat * 100, 2),
        "range_typical": [round(lo, 0), round(hi, 0)],
        "range_1sd": [round(lo1, 0), round(hi1, 0)],
        "atr14": round(atr14, 0),
        "cpr": cpr,
        "factors": latest,
        "already_priced_in": stale,
        "model": {"sessions": int(len(hist)), "gap_error_sd_pct": round(s_gap * 100, 2),
                  "close_error_sd_pct": round(s_c2c * 100, 2)},
    }

    print(f"\nNifty 50 bias for {out['for_session']}  (computed {out['computed_at_ist']} IST)")
    print(f"Previous close      {prev_close:,.0f}   (session {out['last_completed_session']})")
    print(f"\n  BIAS   {bias}      SCORE {score}/100   (chance of closing up: {out['prob_close_up_pct']}%)\n")
    print(f"Expected open       {exp_open:,.0f}   ({gap_hat*100:+.2f}% gap, {out['prob_gap_up_pct']}% chance gap up)")
    print(f"Expected close      {exp_close:,.0f}   ({c2c_hat*100:+.2f}%)")
    print(f"Typical range       {lo:,.0f} to {hi:,.0f}   (expected close +/- half the 14-day ATR of {atr14:,.0f})")
    print(f"Wider range (1 sd)  {lo1:,.0f} to {hi1:,.0f}")
    print(f"\nCPR for the session (from {out['last_completed_session']} H {H:,.0f} / L {L:,.0f} / C {C:,.0f})")
    print(f"  TC {tc:,.0f}   Pivot {piv:,.0f}   BC {bc:,.0f}   width {cpr_width:.3f}% -> {cpr_label.upper()} "
          f"({cpr_pctile:.0f}th percentile of the last year)")
    print(f"  R2 {r2:,.0f}   R1 {r1:,.0f}   S1 {s1:,.0f}   S2 {s2:,.0f}")
    print(f"  Expected open {exp_open:,.0f} is {open_vs_cpr}.")
    print("  Note: over ten years narrow CPR did not mean a trending day for Nifty; wide CPR days actually")
    print("  ranged more, because CPR width just tracks yesterday's volatility. Use the levels, not the label.")
    print("\nOvernight inputs (previous-session move, and its push on today's close in basis points)")
    for f in factors:
        L = latest[f]
        flag = "  already priced into last session" if L["already_priced_in"] else ""
        print(f"  {L['label']:13s} {L['move_pct']:+6.2f}%  as of {L['as_of']}  -> {L['contribution_bp']:+5.1f} bp{flag}")
    if stale:
        print(f"\nNote: {', '.join(stale)} have not closed since Nifty's last session, so their moves are already in the price.")
    print(f"\nModel: {len(hist)} sessions. Close-forecast error sd {s_c2c*100:.2f}%, gap-forecast error sd {s_gap*100:.2f}%.")
    print("A score of 60 means roughly 6 days in 10. Treat 43-57 as no edge.")

    if args.json:
        json.dump(out, open(args.json, "w"), indent=2)
    if args.html:
        write_html(out, args.html)


def write_html(o, path):
    col = {"UP": "#0E6F66", "DOWN": "#A63A2B", "SIDEWAYS": "#5C6B75"}[o["bias"]]
    rows = "".join(
        f"<tr><td>{v['label']}</td><td style='text-align:right'>{v['move_pct']:+.2f}%</td>"
        f"<td style='text-align:right'>{v['contribution_bp']:+.1f} bp</td><td>{v['as_of']}{' (priced in)' if v['already_priced_in'] else ''}</td></tr>"
        for v in o["factors"].values())
    html = f"""<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nifty bias {o['for_session']}</title>
<style>body{{font-family:system-ui,-apple-system,sans-serif;max-width:560px;margin:0 auto;padding:24px 16px;line-height:1.5;color:#1A2228}}
h1{{font-size:1.3rem;margin:0 0 4px}} .big{{font-size:2.6rem;font-weight:700;color:{col};margin:12px 0 0}} .sub{{color:#5C6B75}}
table{{border-collapse:collapse;width:100%;margin-top:16px;font-size:.9rem}} td,th{{padding:6px 4px;border-bottom:1px solid #D9DFE0;text-align:left}}
.kv{{display:grid;grid-template-columns:1fr auto;gap:6px 12px;margin-top:16px}} .kv b{{font-variant-numeric:tabular-nums}}</style></head><body>
<h1>Nifty 50 bias for {o['for_session']}</h1><div class="sub">computed {o['computed_at_ist']} IST · previous close {o['prev_close']:,}</div>
<div class="big">{o['bias']} · {o['score']}/100</div><div class="sub">chance of closing above {o['prev_close']:,.0f}: {o['prob_close_up_pct']}%</div>
<div class="kv"><span>Expected open</span><b>{o['expected_open']:,.0f} ({o['expected_gap_pct']:+.2f}%)</b>
<span>Expected close</span><b>{o['expected_close']:,.0f} ({o['expected_move_pct']:+.2f}%)</b>
<span>Typical range</span><b>{o['range_typical'][0]:,.0f} – {o['range_typical'][1]:,.0f}</b>
<span>Wider range (1 sd)</span><b>{o['range_1sd'][0]:,.0f} – {o['range_1sd'][1]:,.0f}</b></div>
<div class="kv" style="margin-top:14px"><span>CPR width</span><b>{o['cpr']['width_pct']:.3f}% · {o['cpr']['label']}</b>
<span>TC / Pivot / BC</span><b>{o['cpr']['tc']:,.0f} / {o['cpr']['pivot']:,.0f} / {o['cpr']['bc']:,.0f}</b>
<span>R1 / R2</span><b>{o['cpr']['r1']:,.0f} / {o['cpr']['r2']:,.0f}</b>
<span>S1 / S2</span><b>{o['cpr']['s1']:,.0f} / {o['cpr']['s2']:,.0f}</b>
<span>Expected open sits</span><b>{o['cpr']['expected_open_vs_cpr']}</b></div>
<table><tr><th>Input</th><th style='text-align:right'>Move</th><th style='text-align:right'>Push</th><th>As of</th></tr>{rows}</table>
<p class="sub">Score is the model's probability that Nifty closes up. 43–57 is no edge. Range is expected close ± half the 14-day ATR ({o['atr14']:,.0f} pts).</p>
</body></html>"""
    open(path, "w").write(html)


if __name__ == "__main__":
    main()
