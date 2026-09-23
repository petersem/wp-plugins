# Wordpress Plugins
Just a sample project to learn how to do Wordpress plugins. 

## Assessment code
**
- Sitemap in footer (sitemap.php)
- Contact Us (contact-us.php)
- Weather ticker and settings page (weather-ticker.php)

## Other Examples
**example.php**
- Shortcode example
- Direct placement example
- Settings menu page example
- Admin menu page example
- API calling example
- Read/write with wp_options table
- Read/write to custom table

> Check out the github workflow, which packages changed plugins into zips, then puts them in the github release folder. 
> 

## Docker dev container
There is config here for docker dev container which installs php. This way you can actually execute the php locally.

> via vscode command palette `ctrl+shift+p` type `dev container: Reopen in dev container`

> If you want to run without the php xdebugger, type this:
>
> `php -d xdebug.mode=off yourfile.php`