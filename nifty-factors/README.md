# Nifty overnight factor check

Does the previous session's move in crude, the dollar, the US close, the rupee
or other commodities tell you which way Nifty 50 goes today?

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

## Run it

```
pip install pandas numpy requests
python3 nifty_factor_check.py --years 10 --json results.json
```

Downloads are cached in `.cache/` for 12 hours. Yahoo occasionally answers
`429 Too Many Requests`; wait a minute and rerun.

### On an iPhone

- **Google Colab** in Safari: upload the script, run `!python3 nifty_factor_check.py`.
- **a-Shell** or **Pyto** (App Store): `pip install pandas requests`, then run the file.
- Or ask Claude Code from the Claude app to rerun it and publish the results page.

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
