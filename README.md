# Getting Started

Get the Docker stuff up-and-running:

	$ cp .env-example .env
    $ docker-compose up -d

Go to http://localhost:8080/ and configure your demo site.

Go to http://localhost:8080/wp-admin/plugins.php and activate the Tuja plugin.

Configuring e-mail: You must install some kind of e-mail plugin in order for Wordpress 
to be able to send e-mail. One such plugin is https://wordpress.org/plugins/wp-mail-smtp/.

Remember to change Permalink format to "Post name" (or at least not "Plain").

# REST API

Set the secret used to sign authenticaton tokens:

    $ echo "JWT_SECRET=secret" >> .env

Get a token for group with "key" `snpbvx6hde` and then test the `ping` endpoint:

    $ TOKEN=$(curl --silent "http://localhost:8080/wp-json/tuja/v1/tokens" -H "Content-Type: application/json" -d '{"id":"snpbvx6hde"}' | jq -r '.token')

    $ curl -v "http://localhost:8080/wp-json/tuja/v1/ping?token=$TOKEN"

Expected output from the `ping` endpoint:

    {"status":"ok"}

# Unit Tests

    $ brew install composer
    $ composer install
    $ ./vendor/bin/phpunit tests/unit

# Integration Tests

There are a number of integration tests for verifying that the basic use cases are working.

The tests are written in Javascript (using the Jest framework) and use Puppeteer (which uses Google Chrome) for browser automation.

Prerequisites:

* Install `node`
* Install `yarn`
* Run `yarn` in directory `tests/integration`

Running the test:

* Open a terminal in `./` and run `docker-compose up`
* Open a terminal in `./tests/integration` and run `yarn test:visible`

You can alternatively run `yarn test` if you don't want to see the browser window during testing.

## Troubleshooting

Problem: This error is shown when running tests on Mac: "Chromium cannot be opened because the developer cannot be verified."

Solution: See https://support.apple.com/en-us/HT202491.

# Debugging

Enable logging by adding this to your `.env`:

    WORDPRESS_DEBUG        =1

You can also add this if `WORDPRESS_DEBUG` is not enough:

    WORDPRESS_CONFIG_EXTRA =define('WP_DEBUG_LOG', true);

Open shell to Wordpress installation:

    $ docker-compose exec wordpress bash
    bash# tail -f /var/www/html/wp-content/debug.log

# Short Codes

## tuja_form_opens_countdown and tuja_form_closes_countdown

Features:
* Displays the "fuzzy time", e.g. "2 minutes" instead of "132 seconds", until the specified form begins/stops accepting submissions.

Attributes:
* form: The id of the form to check.
* past_format: Pattern string to use when the time has passed. Must include the placeholder "$1", which will be replaced by the actual current countdown value.
* future_format: Pattern string to use when the time has not yet passed. Must include the placeholder "$1", which will be replaced by the actual current countdown value.

# Deployment

This repo is configured to auto deploy to tunnelbanejakten.se. This is done with a webhook and deploy key on the github side, and a mu-plugin on the server side.
    
# To Do

See https://github.com/tunnelbanejakten/wp-tuja/issues