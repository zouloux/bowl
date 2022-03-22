# bowl
Advanced CMS fields workflow on top of Wordplate for Wordpress.


### Install

1. Set this to `composer.json` to install as a `mu-plugin` with Bedrock.

```json5
{
	// ...
	"extra": {
		"installer-paths": {
			"public/mu-plugins/{$name}": [
				"type:wordpress-muplugin",
				"zouloux/bowl"
				// ... other plugins
			],
			"public/plugins/{$name}": [
				"type:wordpress-plugin"
				// ... other plugins
			],
			"public/themes/{$name}": [
				"type:wordpress-theme"
				// ... themes
			]
		},
		"wordpress-install-dir": "public/wordpress"
	},
	// ...
}
```

2. Install with composer

    $ `composer require zouloux/bowl`

### Requirements

- **Bowl** uses [Wordplate](https://github.com/wordplate/wordplate) (`wordplate/framework`).
- **Bowl** needs `wpackagist-plugin/advanced-custom-fields-pro` and `wordplate/acf`
- **Bowl** optionally uses `wpackagist-plugin/wp-multilang` as mutli lang plugin.

Other plugins you should use with Bowl :

- `wordplate/mail`
- `wpackagist-plugin/admin-ui-cleaner`
- `wpackagist-plugin/disable-comments`
- `wpackagist-plugin/flamingo`
- `wpackagist-plugin/post-types-order`
- `wpackagist-plugin/regenerate-thumbnails`
- `wpackagist-plugin/wp-limit-login-attempts`

### Documentation

#### TODO : Create a new bowl app with https://github.com/zouloux/starters
#### TODO : How to configure a Bowl app
#### TODO : Use it with Nano and twig to render templates