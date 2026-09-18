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

### Is CPR in the score?

Partly, and only where it helps. Four CPR-derived inputs from the previous
session (CPR width, close vs pivot, close position within the day's range,
and the day's return) feed the **close** model. The **gap** model stays on the
overnight factors only. Tested on the last 30% of sessions (729 days) held
out from fitting:

| feature set             | close hit % | close corr | gap hit % | gap corr |
|-------------------------|-------------|------------|-----------|----------|
| overnight factors only  | 56.4        | 0.219      | 69.4      | 0.443    |
| + CPR width             | 56.8        | 0.229      | 69.4      | 0.443    |
| + prev close vs pivot   | 56.4        | 0.217      | 68.2      | 0.428    |
| + prev close position   | 56.8        | 0.216      | 68.6      | 0.439    |
| + prev day return       | 55.8        | 0.243      | 68.4      | 0.431    |
| + all four              | 56.9        | 0.266      | 67.9      | 0.448    |
| CPR features alone      | 54.6        | 0.123      | 59.3      | 0.130    |

Half a point of hit rate on the close, nothing on the gap. The script also
prints a **CPR read** (expected open above TC = UP, below BC = DOWN, inside =
SIDEWAYS) and whether it agrees with the bias. It usually will, because the
expected open comes from the same overnight factors, so treat agreement as a
sanity check rather than independent confirmation.

Run it between about 07:00 and 09:15 IST for the cleanest read, after the US
close and before India opens. Run after 15:45 IST it targets the next session,
and the US inputs will still be the previous night's until the US closes.

### How much score confirms the call, and can you trade it?

Walk-forward test: the models are refitted at the start of each year on all
earlier data and scored on that year, 2020 to 2026, 1,640 sessions the
models never saw while fitting. "Right" means Nifty closed in the called
direction versus the previous close. The last two columns are the only
trade you can actually place: enter at 9:15 in the score's direction, exit
at the close.

| score band       | days/yr | right, close vs prev close | avg move that way | right, open to close | avg open-to-close |
|------------------|---------|----------------------------|-------------------|----------------------|-------------------|
| 30 or below      | 12      | 71.6%                      | +0.44%            | 45.7%                | -0.21%            |
| 31-35            | 10      | 62.1%                      | +0.50%            | 54.5%                | +0.05%            |
| 36-43            | 34      | 61.6%                      | +0.20%            | 53.1%                | +0.07%            |
| 44-57 sideways   | 109     | 53.9%                      | +0.03%            | 48.5%                | -0.01%            |
| 58-65            | 47      | 60.8%                      | +0.23%            | 45.3%                | -0.08%            |
| 66-70            | 19      | 64.8%                      | +0.26%            | 48.8%                | -0.08%            |
| above 70         | 18      | 74.8%                      | +0.47%            | 48.7%                | -0.11%            |

Two things follow.

1. **The score is honest.** Above 70 or at 30 and below it was right about
   three days in four; 58-65 about six in ten; the sideways band is a coin
   flip as designed.
2. **You cannot trade it from the open.** The whole edge is the gap, which
   is in the price at 9:15. Buying the open on a high score, or selling the
   open on a low score, was right less than half the time and lost money on
   average before costs. If anything, strong gaps gave a little back during
   the day.

So the rule "take the trade when the score is above X" does not exist for
an intraday entry, at any X. What the score is good for:

- knowing the likely opening gap and its size before the bell, so you are
  not surprised and can plan levels;
- deciding whether to hold or hedge an overnight position (the score for
  tomorrow is known around 02:00 IST, after the US close, but the position
  has to be on from today's close);
- context for CPR and pivot levels: an expected open above R1 with a high
  score is a gap-and-hold setup to watch, not a buy signal.

Year by year, confident calls (58 and above, 42 and below) were right
between 57% (2025) and 69% (2023-24) on close vs previous close. It never
fell to a coin flip, and it never approached the certainty a fixed
"take every trade" rule would need.

The gap score prints separately and is much sharper: above 80 it called the
gap direction right 91% of the time, 20 and below 84%. It is not tradeable
for the same reason, but it is the number to trust for "which way will we open".

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
