publish-google-news
===================

Wordpress plugin to allow publishing of google news items

If autopublish is desired from a password-protected page using cron, modify wp-includes/post-template.php:

```php
function post_password_required( $post = null ) {
        $post = get_post($post);

        if ( empty( $post->post_password ) )
                return false;

        if ( $_GET['post_password'] == $post->post_password )
                return false;
```

And use

```bash
wget -q -O - http://localhost/?pageid=n&post_password=password > /tmp/last_autopost.html 2>&1
```

in crontab