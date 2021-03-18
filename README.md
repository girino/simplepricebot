# Simple Price Bot

This bot gets tickers from binance and posts them to telegram. It also watches one of the tickers for
a target value and marks you in the message when target value is attaines, so you get a notification
on telegram.

Made for personal use, but might be useful for someone else, at least to leran how to code a bot.

## usage

Edit config.php.sample and rename it to config.php. Then run `php main.php`

Alternatelly, use docker-compose: `docker-compose up -d`