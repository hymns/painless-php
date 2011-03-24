# Painless-PHP, walk the painless path #

## Quick Start ##

Painless-PHP is primarily used to develop web applications with the option of exposing RESTful API interfaces, and is built to make organizing and developing a project as easy as possible. First off, get familiar with the morphine toolkit. In Painless, Morphine (mo) is used to perform a wide variety of tasks, such as generating scaffolding files, installing the framework, upgrading modules or apps, registering new apps, and so forth. It's your swiss army knife, and you can access it both via the command line (php mo) or the web interface (which we highly recommend).

1. Download the lastest stable version of Painless and unzip it to your web folder (www, htdocs, etc)
2. Open your command line, navigate to the directory that you installed painless on, and run `php mo install --new`
3. You will be asked to enter 3 things: (1) the name of your default app, (2) the path of the app to save to, and (3) what kind of entry point (HTTP, command line, cron, etc) your app is expecting by default
