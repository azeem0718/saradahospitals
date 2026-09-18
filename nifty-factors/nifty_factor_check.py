#!/usr/bin/env python3
"""
Does yesterday's crude / dollar / US close / USD-INR tell you where Nifty goes today?

Pulls ~10 years of daily closes from Yahoo Finance (no API key) and measures,
for each overnight factor, how well its previous-session move predicts:
  1. Nifty's opening gap      (today's open  vs yesterday's close)
  2. Nifty's intraday move    (today's close vs today's open)
  3. Nifty's close-to-close   (today's close vs yesterday's close)

For each pair it reports the correlation and the "direction hit rate":
the share of days where the sign of the factor move matched the sign of the
Nifty move. 50% = coin flip.

Usage:  python3 nifty_factor_check.py [--years 10] [--json out.json]
Needs:  pandas, numpy, requests
"""
import argparse, json, sys, time
import numpy as np
import pandas as pd
import requests

TICKERS = {
    "nifty":  "^NSEI",     # Nifty 50
    "spx":    "^GSPC",     # S&P 500 (US close, previous night)
    "crude":  "CL=F",      # WTI crude front month
    "brent":  "BZ=F",      # Brent crude
    "dxy":    "DX-Y.NYB",  # US dollar index
    "usdinr": "USDINR=X",  # USD/INR
    "gold":   "GC=F",      # Gold
    "copper": "HG=F",      # Copper (proxy for metals)
    "nikkei": "^N225",     # Nikkei, same-morning Asia tone
    "vix":    "^VIX",      # US VIX
}

HDRS = {"User-Agent": "Mozilla/5.0"}

def fetch(symbol, years, cache_dir=".cache"):
    import os
    os.makedirs(cache_dir, exist_ok=True)
    cache = os.path.join(cache_dir, f"{symbol.replace('^','_').replace('=','_')}_{years}y.csv")
    if os.path.exists(cache) and time.time() - os.path.getmtime(cache) < 12 * 3600:
        return pd.read_csv(cache, index_col=0, parse_dates=True)
    url = f"https://query2.finance.yahoo.com/v8/finance/chart/{requests.utils.quote(symbol)}"
    params = {"range": f"{years}y", "interval": "1d"}
    for attempt in range(4):
        r = requests.get(url, params=params, headers=HDRS, timeout=30)
        if r.status_code == 200:
            break
        time.sleep(2 * (attempt + 1))
    r.raise_for_status()
    res = r.json()["chart"]["result"][0]
    q = res["indicators"]["quote"][0]
    df = pd.DataFrame({"open": q["open"], "close": q["close"]},
                      index=pd.to_datetime(res["timestamp"], unit="s", utc=True))
    df.index = df.index.tz_convert("Asia/Kolkata").normalize().tz_localize(None)
    df = df[~df.index.duplicated(keep="last")].dropna()
    df.to_csv(cache)
    time.sleep(1.5)  # be polite to Yahoo, avoids 429s
    return df

def hit_rate(a, b):
    m = (a != 0) & (b != 0)
    return float((np.sign(a[m]) == np.sign(b[m])).mean()), int(m.sum())

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--years", type=int, default=10)
    ap.add_argument("--json", default=None)
    args = ap.parse_args()

    data = {}
    for name, sym in TICKERS.items():
        try:
            data[name] = fetch(sym, args.years)
            print(f"fetched {name:7s} {sym:10s} {len(data[name])} rows", file=sys.stderr)
        except Exception as e:
            print(f"skip {name} ({sym}): {e}", file=sys.stderr)

    n = data["nifty"]
    nifty = pd.DataFrame({
        "gap":       n["open"] / n["close"].shift(1) - 1,   # overnight gap
        "intraday":  n["close"] / n["open"] - 1,            # open -> close
        "c2c":       n["close"] / n["close"].shift(1) - 1,  # close -> close
    }).dropna()

    # Factor = previous session's close-to-close % move, aligned to the next Nifty day.
    # For US/commodity/FX series, the "previous session" is the one that closed before
    # India's open, so we take the last available close strictly before each Nifty date.
    rows = []
    for name, df in data.items():
        if name == "nifty":
            continue
        ret = (df["close"].pct_change()).dropna()
        ret.name = name
        # as-of merge: last factor close BEFORE the Nifty date
        merged = pd.merge_asof(nifty.reset_index().rename(columns={"index": "date"}).sort_values("date"),
                               ret.reset_index().rename(columns={"index": "fdate"}).sort_values("fdate"),
                               left_on="date", right_on="fdate", direction="backward",
                               allow_exact_matches=False)
        merged = merged.dropna(subset=[name])
        # ignore stale factor data older than 4 calendar days (long weekends etc.)
        merged = merged[(merged["date"] - merged["fdate"]).dt.days <= 4]
        f = merged[name].values
        row = {"factor": name, "n": int(len(merged))}
        for target in ("gap", "intraday", "c2c"):
            t = merged[target].values
            corr = float(np.corrcoef(f, t)[0, 1]) if len(t) > 2 else float("nan")
            hr, cnt = hit_rate(f, t)
            row[f"{target}_corr"] = round(corr, 3)
            row[f"{target}_hit"] = round(hr * 100, 1)
        rows.append(row)

    # Baselines: does yesterday's Nifty itself predict today's? Does the gap predict the intraday?
    base = []
    prev = nifty["c2c"].shift(1).dropna()
    for target in ("gap", "intraday", "c2c"):
        t = nifty[target].loc[prev.index]
        base.append({"factor": "nifty_prev_day", "target": target,
                     "corr": round(float(np.corrcoef(prev, t)[0, 1]), 3),
                     "hit": round(hit_rate(prev.values, t.values)[0] * 100, 1)})
    base.append({"factor": "todays_gap", "target": "intraday",
                 "corr": round(float(np.corrcoef(nifty["gap"], nifty["intraday"])[0, 1]), 3),
                 "hit": round(hit_rate(nifty["gap"].values, nifty["intraday"].values)[0] * 100, 1)})

    out = pd.DataFrame(rows).set_index("factor")
    pd.set_option("display.width", 160)
    print(f"\nNifty 50 daily data: {nifty.index.min().date()} to {nifty.index.max().date()}, {len(nifty)} sessions\n")
    print("Previous-session factor move vs today's Nifty move")
    print("corr = correlation (-1..1), hit = % of days the direction matched (50 = coin flip)\n")
    print(out.to_string())
    print("\nBaselines")
    print(pd.DataFrame(base).to_string(index=False))
    up = (nifty["c2c"] > 0).mean() * 100
    print(f"\nNifty closed up on {up:.1f}% of all days (the 'always say up' benchmark).")

    if args.json:
        with open(args.json, "w") as fh:
            json.dump({"start": str(nifty.index.min().date()), "end": str(nifty.index.max().date()),
                       "sessions": int(len(nifty)), "up_days_pct": round(up, 1),
                       "factors": rows, "baselines": base}, fh, indent=2)

if __name__ == "__main__":
    main()
