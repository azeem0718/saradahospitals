#!/usr/bin/env python3
"""
After-9:15 rules for Nifty 50, tested on intraday bars.

The pre-market score (nifty_daily_bias.py) predicts the opening gap well and the
close vs previous close moderately, but entering at 9:15 in its direction has no
edge. This script tests what you can do AFTER the open, using intraday bars:

  ORB15 / ORB30   opening-range breakout: long on first bar close above the
                  15/30-minute range high, short below its low; exit at close.
                  A "stop" variant exits if price crosses the range midpoint.
  FH              first-hour direction: at 10:15, long if price > day open, else
                  short; exit at close.
  CPR             at 9:30 (or 10:15 on hourly bars): long if price above TC,
                  short if below BC, nothing inside; exit at close.
  GAPGO / GAPFADE on gaps larger than 0.3%: gap-and-go trades with the gap if
                  price is still on the gap side of the open at 10:15; gap-fade
                  takes the opposite.

Each rule is reported raw, then filtered by the pre-market bias (only trades in
the direction of a score >= 58 or <= 42), so you can see whether the score adds
anything once you are inside the session.

Data: --csv <file> of intraday bars (columns auto-detected: a datetime or
date+time column plus open/high/low/close; any bar size from 1 to 60 minutes).
Without --csv it pulls what Yahoo offers: 60 days of 5-minute bars and about
two years of hourly bars. Ten years of 1- or 5-minute spot data is what makes
this conclusive.

Usage:  python3 nifty_intraday_rules.py [--csv data/nifty_spot_intraday.csv] [--cost 0.03] [--json out.json]
"""
import argparse, json, sys, time
import numpy as np
import pandas as pd
import requests
from nifty_daily_bias import fetch, align_factors, ols, predict, norm_cdf, TICKERS, FACTORS

HDRS = {"User-Agent": "Mozilla/5.0"}


# ----------------------------------------------------------------------------- data
def load_csv(path):
    df = pd.read_csv(path)
    cols = {c.lower().strip(): c for c in df.columns}
    def pick(*names):
        for nme in names:
            if nme in cols:
                return cols[nme]
        return None
    dt = pick("datetime", "timestamp", "date_time", "time_stamp")
    if dt:
        ts = pd.to_datetime(df[dt])
    else:
        d, t = pick("date"), pick("time")
        if d is None:
            sys.exit("CSV needs a datetime column, or date + time columns")
        ts = pd.to_datetime(df[d].astype(str) + " " + (df[t].astype(str) if t else "00:00"))
    if ts.dt.tz is not None:
        ts = ts.dt.tz_convert("Asia/Kolkata").dt.tz_localize(None)
    out = pd.DataFrame({"open": df[pick("open", "o")], "high": df[pick("high", "h")],
                        "low": df[pick("low", "l")], "close": df[pick("close", "c", "ltp")]}, index=ts)
    out = out.sort_index().dropna()
    # keep regular session only
    out = out[(out.index.time >= pd.Timestamp("09:15").time()) & (out.index.time < pd.Timestamp("15:30").time())]
    return out


def load_yahoo(interval, rng):
    url = "https://query2.finance.yahoo.com/v8/finance/chart/%5ENSEI"
    for attempt in range(4):
        r = requests.get(url, params={"interval": interval, "range": rng}, headers=HDRS, timeout=30)
        if r.status_code == 200:
            break
        time.sleep(2 * (attempt + 1))
    r.raise_for_status()
    res = r.json()["chart"]["result"][0]
    q = res["indicators"]["quote"][0]
    df = pd.DataFrame({"open": q["open"], "high": q["high"], "low": q["low"], "close": q["close"]},
                      index=pd.to_datetime(res["timestamp"], unit="s", utc=True).tz_convert("Asia/Kolkata").tz_localize(None))
    df = df.dropna()
    df = df[(df.index.time >= pd.Timestamp("09:15").time()) & (df.index.time < pd.Timestamp("15:30").time())]
    time.sleep(1.5)
    return df


def bar_minutes(df):
    d = pd.Series(df.index).diff().dt.total_seconds().div(60).dropna()
    return int(d[d > 0].mode().iloc[0])


# ----------------------------------------------------------------------------- daily context
def daily_scores(years=10):
    """Walk-forward pre-market score for every session (refit each calendar year on earlier data)."""
    data = {k: fetch(v[0], years) for k, v in TICKERS.items()}
    n = data["nifty"]
    hist = pd.DataFrame({"gap": n.open / n.close.shift(1) - 1, "c2c": n.close / n.close.shift(1) - 1}).dropna()
    rets = {f: data[f].close.pct_change().dropna() for f in FACTORS}
    X = align_factors(hist.index, rets).dropna()
    pv = (n.high + n.low + n.close) / 3
    cprf = pd.DataFrame({"cpr_width": (((2 * pv - (n.high + n.low) / 2) - (n.high + n.low) / 2).abs() / pv).shift(1),
                         "close_vs_pivot": ((n.close - pv) / pv).shift(1),
                         "close_pos": ((n.close - n.low) / (n.high - n.low)).shift(1) - 0.5,
                         "prev_ret": n.close.pct_change().shift(1)})
    X = pd.concat([X, cprf.loc[X.index]], axis=1).dropna()
    hist = hist.loc[X.index]
    CPR = list(cprf.columns)
    scores = {}
    first = X.index.year.min() + 3
    for yr in range(first, X.index.year.max() + 1):
        tr = X.index.year < yr; te = X.index.year == yr
        if te.sum() == 0 or tr.sum() < 200:
            continue
        mu, sd = X[tr].mean(), X[tr].std()
        Z = ((X - mu) / sd).clip(-4, 4)
        bc, sc = ols(Z[tr][FACTORS + CPR].values, hist.c2c[tr].values)
        for i in np.where(te)[0]:
            scores[X.index[i]] = round(norm_cdf(predict(bc, Z[FACTORS + CPR].values[i]) / sc) * 100)
    s = pd.Series(scores, name="score")
    daily = n[["open", "high", "low", "close"]].copy()
    return daily, s


# ----------------------------------------------------------------------------- rules
def run_rules(bars, daily, scores, cost_pct, or_minutes=(15, 30)):
    m = bar_minutes(bars)
    days = sorted(set(bars.index.normalize()))
    trades = []
    for d in days:
        db = bars[bars.index.normalize() == d]
        if len(db) < 3:
            continue
        # previous session from the daily series (prefer daily file; fallback to intraday)
        prev = daily[daily.index < d]
        if prev.empty:
            continue
        p = prev.iloc[-1]
        pc = float(p.close)
        piv = (p.high + p.low + p.close) / 3; bc_ = (p.high + p.low) / 2; tc = 2 * piv - bc_
        tc, bc_ = max(tc, bc_), min(tc, bc_)
        day_open = float(db.open.iloc[0]); day_close = float(db.close.iloc[-1])
        gap = day_open / pc - 1
        score = scores.get(pd.Timestamp(d), np.nan)
        bias_dir = 1 if score >= 58 else -1 if score <= 42 else 0
        t0 = db.index[0]

        def after(minutes):
            return db[db.index >= t0 + pd.Timedelta(minutes=minutes)]

        def price_at(minutes):
            x = db[db.index < t0 + pd.Timedelta(minutes=minutes)]
            return float(x.close.iloc[-1]) if len(x) else None

        def rec(rule, direction, entry, exit_, extra=None):
            if direction == 0 or entry is None:
                return
            ret = direction * (exit_ / entry - 1) * 100
            trades.append({"date": d, "rule": rule, "dir": direction, "ret_pct": ret,
                           "net_pct": ret - cost_pct, "score": score, "bias_dir": bias_dir,
                           "with_bias": (bias_dir == direction), "against_bias": (bias_dir == -direction),
                           **(extra or {})})

        # --- opening range breakouts (need bars finer than the range)
        for orm in or_minutes:
            if m > orm:
                continue
            orb = db[db.index < t0 + pd.Timedelta(minutes=orm)]
            hi, lo = float(orb.high.max()), float(orb.low.min())
            mid = (hi + lo) / 2
            rest = after(orm)
            direction, entry, entry_i = 0, None, None
            for i, (ts, row) in enumerate(rest.iterrows()):
                if row.close > hi:
                    direction, entry, entry_i = 1, float(row.close), i; break
                if row.close < lo:
                    direction, entry, entry_i = -1, float(row.close), i; break
            if direction:
                rec(f"ORB{orm}", direction, entry, day_close)
                # stop variant: exit if a later close crosses the range midpoint
                exit_ = day_close
                for ts, row in rest.iloc[entry_i + 1:].iterrows():
                    if (direction == 1 and row.close < mid) or (direction == -1 and row.close > mid):
                        exit_ = float(row.close); break
                rec(f"ORB{orm}+stop", direction, entry, exit_)

        # --- first-hour direction
        p60 = price_at(60 + m) if m < 60 else (float(db.close.iloc[0]) if m == 60 else None)
        if p60 is not None and p60 != day_open:
            direction = 1 if p60 > day_open else -1
            rec("FH", direction, p60, day_close)

        # --- CPR at 9:30 (hourly data: first bar close = 10:15)
        p30 = price_at(15 + m) if m <= 15 else p60
        if p30 is not None:
            direction = 1 if p30 > tc else -1 if p30 < bc_ else 0
            rec("CPR", direction, p30, day_close)

        # --- gaps
        if abs(gap) >= 0.003 and p60 is not None:
            gdir = 1 if gap > 0 else -1
            holding = (gdir == 1 and p60 >= day_open) or (gdir == -1 and p60 <= day_open)
            if holding:
                rec("GAPGO", gdir, p60, day_close, {"gap_pct": gap * 100})
            else:
                rec("GAPFADE", -gdir, p60, day_close, {"gap_pct": gap * 100})
    return pd.DataFrame(trades), m


def summarise(t):
    def stats(x):
        if len(x) == 0:
            return pd.Series({"trades": 0})
        w = x[x.net_pct > 0]; l = x[x.net_pct <= 0]
        pf = w.net_pct.sum() / -l.net_pct.sum() if len(l) and l.net_pct.sum() < 0 else np.inf
        return pd.Series({"trades": len(x), "win_pct": (x.net_pct > 0).mean() * 100,
                          "avg_net_pct": x.net_pct.mean(), "avg_win": w.net_pct.mean() if len(w) else 0,
                          "avg_loss": l.net_pct.mean() if len(l) else 0, "profit_factor": pf,
                          "total_net_pct": x.net_pct.sum()})
    out = {}
    for rule, g in t.groupby("rule"):
        out[(rule, "all trades")] = stats(g)
        out[(rule, "with bias only")] = stats(g[g.with_bias])
        out[(rule, "against bias")] = stats(g[g.against_bias])
    return pd.DataFrame(out).T


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--csv", default=None, help="intraday bars CSV (datetime, open, high, low, close)")
    ap.add_argument("--cost", type=float, default=0.03, help="round-trip cost in %% of notional (default 0.03)")
    ap.add_argument("--json", default=None)
    args = ap.parse_args()

    print("building walk-forward pre-market scores...", file=sys.stderr)
    daily, scores = daily_scores()
    scores = scores.to_dict()

    sets = []
    if args.csv:
        bars = load_csv(args.csv)
        sets.append((f"CSV {args.csv}", bars))
    else:
        sets.append(("Yahoo 5-minute, last 60 days", load_yahoo("5m", "60d")))
        sets.append(("Yahoo hourly, last 2 years", load_yahoo("60m", "730d")))

    results = {}
    pd.set_option("display.width", 200); pd.set_option("display.float_format", lambda v: f"{v:8.2f}")
    for label, bars in sets:
        t, m = run_rules(bars, daily, scores, args.cost)
        if t.empty:
            print(f"\n{label}: no trades"); continue
        days = t.date.nunique()
        print(f"\n=== {label}: {m}-minute bars, {days} sessions {t.date.min().date()} to {t.date.max().date()}, "
              f"cost {args.cost}% per round trip ===")
        summ = summarise(t)
        print(summ.to_string())
        if days > 300:
            print("\nBy year, all trades, win % / avg net %:")
            yr = t.groupby([t.rule, t.date.dt.year]).agg(win=("net_pct", lambda x: (x > 0).mean() * 100),
                                                          avg=("net_pct", "mean"), n=("net_pct", "size"))
            print(yr.unstack(0).round(2).to_string())
        results[label] = {"bars_minutes": m, "sessions": int(days),
                          "summary": {f"{r} | {k}": v.to_dict() for (r, k), v in summ.iterrows()}}

    print("\nHow to read: win% is after costs; a rule needs a profit factor comfortably above 1.0 AND a")
    print("similar picture in every year before it deserves money. 'with bias only' shows whether the")
    print("pre-market score improves the rule; if it does not, the score has no intraday role.")
    if args.json:
        json.dump(results, open(args.json, "w"), indent=2, default=str)


if __name__ == "__main__":
    main()
