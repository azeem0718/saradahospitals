Put your intraday Nifty 50 spot file here, e.g. `data/nifty_spot_intraday.csv`.
This folder is git-ignored so the file is never committed.

Accepted layouts (column names are matched case-insensitively):

    datetime,open,high,low,close            2016-09-20 09:15:00,8800.5,8805.0,8798.2,8801.0
    date,time,open,high,low,close           20/09/2016,09:15,8800.5,8805.0,8798.2,8801.0
    timestamp,o,h,l,c

Any bar size from 1 to 60 minutes. Extra columns (volume, oi) are ignored.
Then run from `nifty-factors/`:

    python3 nifty_intraday_rules.py --csv data/nifty_spot_intraday.csv --json intraday_results.json
