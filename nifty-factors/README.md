# Nifty overnight factor check

Does the previous session's move in crude, the dollar, the US close, the rupee
or other commodities tell you which way Nifty 50 goes today?

Two scripts:

- `nifty_daily_bias.py` gives **today's call**: UP / DOWN / SIDEWAYS, a 0-100
  score, expected open and close, and an approximate range.
- `nifty_factor_check.py` measures how well each factor has predicted Nifty
  over the last ten years, which is what the daily call is built on.

## Today's bias

```
python3 nifty_daily_bias.py --json bias.json --html today.html
```

Prints something like:

```
Nifty 50 bias for 2026-09-18  (computed 2026-09-18 11:37 IST)
Previous close      23,271   (session 2026-09-17)

  BIAS   UP      SCORE 64/100   (chance of closing up: 63.8%)

Expected open       23,378   (+0.46% gap, 79.2% chance gap up)
Expected close      23,351   (+0.35%)
Typical range       23,254 to 23,449   (expected close +/- half the 14-day ATR of 195)
Wider range (1 sd)  23,124 to 23,579
```

followed by each overnight input and how many basis points it pushes the call.
`today.html` is a one-screen page of the same numbers you can open in Safari.

How the call is made:

1. Two linear regressions are fitted on the ten-year history: opening gap and
   close-to-close move, each against the previous session's standardised moves
   in the S&P 500, VIX, dollar index, WTI, gold, copper, Nikkei and USD/INR.
2. The latest factor moves are plugged in to get the expected gap and expected
   full-day move. Bars still in progress (today's US or FX session) are ignored.
3. **Score** = the model's probability that Nifty closes above the previous
   close, using the historical spread of its own errors.
   `>= 57` is UP, `<= 43` is DOWN, anything between is SIDEWAYS. An expected
   move under 0.1% is also SIDEWAYS.
4. **Range** = expected close +/- half the 14-day average true range. The wider
   band is +/- one standard deviation of the close model's error.

It also prints the CPR (central pivot range) and floor pivots for the session:

```
CPR for the session (from 2026-09-17 H / L / C)
  TC ...   Pivot ...   BC ...   width 0.xxx% -> NARROW / AVERAGE / WIDE (percentile of the last year)
  R2 ...   R1 ...   S1 ...   S2 ...
  Expected open ... is above / inside / below the CPR.
```

### Narrow vs wide CPR: what ten years of Nifty say

The popular rule is "narrow CPR means a trending day, wide CPR means a
sideways day". The data does not support it. Bucketing each session by
its CPR width against the trailing year (2,216 sessions, 2017 to 2026):

| CPR width bucket   | days | median day range | median close-to-close move | trend days* |
|--------------------|------|------------------|----------------------------|-------------|
| narrow (<33rd pct) | 719  | 0.86%            | 0.47%                      | 30.5%       |
| average            | 766  | 0.86%            | 0.51%                      | 33.8%       |
| wide (>67th pct)   | 731  | 1.04%            | 0.61%                      | 35.0%       |

By fixed width:

| CPR width  | days | median day range | median move | trend days* |
|------------|------|------------------|-------------|-------------|
| < 0.25%    | 1575 | 0.85%            | 0.49%       | 32.0%       |
| 0.25-0.5%  | 498  | 1.03%            | 0.56%       | 34.9%       |
| 0.5-1%     | 120  | 1.35%            | 0.80%       | 38.3%       |
| > 1%       | 23   | 3.64%            | 2.50%       | 43.5%       |

\* a trend day closes in the top or bottom fifth of its own range and moves more than 0.5%.

Wide CPR days ranged **more** and trended slightly **more**, not less. CPR
width is just yesterday's high-low range in disguise, and volatility
persists from one day to the next. So: use the CPR and pivot levels as
reference points for the open and for intraday support and resistance, and
read the width as "expect a quiet day" or "expect a big day", never as a
trend-or-chop call.

Where the open lands relative to the CPR does line up with the day's close,
but only because it is the gap in another form:

| open location    | days | closed above prev close | closed above its open |
|------------------|------|-------------------------|-----------------------|
| above TC         | 1435 | 66.2%                   | 47.2%                 |
| inside the CPR   | 98   | 36.7%                   | 46.9%                 |
| below BC         | 882  | 37.5%                   | 49.0%                 |

An open above the CPR closed above the previous close two-thirds of the
time, but closed above its own open less than half the time. The gap
carries the information; the intraday leg is still a coin flip.

Run it between about 07:00 and 09:15 IST for the cleanest read, after the US
close and before India opens. Run after 15:45 IST it targets the next session,
and the US inputs will still be the previous night's until the US closes.

Be honest with yourself about the score. Almost all of the edge is the S&P 500
predicting the opening gap. The close-to-close forecast has an error sd of
about 1%, so scores rarely leave the 40-60 band and a 64 means roughly 6 days
in 10, not a sure thing.

## Ten-year factor check

`nifty_factor_check.py` pulls ~10 years of daily closes from Yahoo Finance
(no API key needed) and reports, for each factor, the correlation and the
direction hit rate against three Nifty moves:

| target     | meaning                                   |
|------------|-------------------------------------------|
| `gap`      | today's open vs yesterday's close         |
| `intraday` | today's close vs today's open             |
| `c2c`      | today's close vs yesterday's close        |

A hit rate of 50% is a coin flip. Nifty closes up on ~54% of days, so a factor
must beat that to be useful.

## Setup

```
pip install pandas numpy requests
python3 nifty_factor_check.py --years 10 --json results.json
```

Downloads are cached in `.cache/` for 12 hours. Yahoo occasionally answers
`429 Too Many Requests`; wait a minute and rerun.

### On an iPhone

- **Google Colab** in Safari: upload the scripts, run `!python3 nifty_daily_bias.py --html today.html`, then open `today.html` from the file panel.
- **a-Shell** or **Pyto** (App Store): `pip install pandas requests`, then run the file.
- Or ask Claude Code from the Claude app for today's bias; it reruns the script and updates the results page.

## Results on 2026-09-18 (2,466 sessions, Sep 2016 to Sep 2026)

Direction hit rate, % of days the factor's sign matched Nifty's sign.
Values well below 50 mean the *opposite* direction held (e.g. VIX up, Nifty gap down).

| factor       | gap  | intraday | close-to-close |
|--------------|------|----------|----------------|
| S&P 500      | 68.4 | 48.8     | 59.2           |
| VIX          | 34.1 | 50.8     | 42.3           |
| Dollar index | 43.5 | 50.0     | 45.6           |
| Copper       | 56.6 | 49.5     | 53.5           |
| Gold         | 55.1 | 50.2     | 53.2           |
| WTI crude    | 53.0 | 50.9     | 52.9           |
| Brent crude  | 53.7 | 50.6     | 52.1           |
| Nikkei 225   | 51.3 | 50.0     | 50.8           |
| USD/INR      | 48.4 | 49.9     | 48.4           |

Takeaways:

- The US close and VIX predict the **opening gap** about two-thirds of the time. That is priced in by 9:15.
- Crude, gold, copper, the rupee and the Nikkei are within a few points of a coin flip.
- **No factor predicts the open-to-close move.** Every one lands between 49 and 51%.

Full numbers including correlations are in `results.json`.
