# How to Generate Valid RSS Feed for your Laravel Application?
Feeds are one of the traditional and most effective way to distribute your content to wider audience. There are thousands of apps such as Feedly, Apple News, Google Newsstand, that allows people to follow and read your content whenever new content is published.

Feeds allows web masters to drive traffic to their website. Some of the feed delivery platform such as Google Feed burner allows your to monetize your feed content by serving ads.

# Generate RSS Feed in Laravel
According to web standards, there are different feed formats that are widely accepted. RSS and Atom are the most popular ones. Feeds are XML based, however they differ in specifications.

To make our life easy, we will generate valid RSS and Atom feed using roumen/feed Laravel open source library. In this article, we assume you have the basic knowledge of Laravel project and have a project handy to integrate.

# Adding roumen/feed via composer
Laravel project dependencies are maintained using composer. We can add the roumen/feed dependency library using the following artisan command:

```
composer require roumen/feed
```

Or add the following to your re composer.json file:
```
"roumen/feed": "~2.10"
```

Please note, after updating composer.json file, run _composer install_ command to add dependency to project.

Now, register for _Roumen\Feed\FeedServiceProvider_ service provider and class alias in your Laravel _config/app/php_ file.

```php
<?php
return [
     //...		
    'providers' => [
        //...
        Roumen\Feed\FeedServiceProvider::class,
    ],

    'aliases' => [		
	//...
        'Feed'      => Roumen\Feed\Feed::class,
    ],
];
```

Optionally, if you want to alter the blade layouts, you can publish vendor views using following artisan command.

```
artisan vendor:publish --provider="Roumen\Feed\FeedServiceProvider"artisan vendor:publish --provider="Roumen\Feed\FeedServiceProvider"
```

# Eloquent Models
This tutorial scope is limited to generating RSS feed, and hence we wont cover the Eloquent Models and database concepts.

The following code snippet of the Post and User model are illustrated  just to get idea of how my data in database are stored.

```php
<?php
namespace App\Models;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';
    public $timestamps = TRUE;
    protected $fillable = [
        'id',
        'user_id',
        "title",
        'content',
        "excerpt",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }   
}
```

Let us now see how the User model looks like.

```php
<?php
namespace App;
use App\Models\Post;

class User extends Authenticatable
{
    protected $table = 'users';
    use Notifiable;	
    protected $fillable = [
        'id',
        'fname',
        'lname',
        'email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function post()
    {
        return $this->hasMany(Post::class);
    }
}
```

Notice that in the above relationship, we have inverse one to many relationship between Post and User. A user can have multiple posts and a post can be associated to one user.

# Routes for Feed

Here is how our routes for posts looks like,

```php
#Post archive
Route::get('posts/', ['as' => 'post.archive', 'uses' => 'PostController@archive']);
#Single
Route::get('post/{id}/{slug?}', ['as' => 'post.single', 'uses' => 'PostController@single']);
```

# Feed Configuration

We would like to have some of the feed configurations inside a config file. Create a new file named feed.php inside /config/ directory and add the following snippets.

```php
<?php
return [
    'feed_title' => "Stacktips",
    'feed_description' => 'Your description',
    'feed_logo' => 'http://example.com/images/brand/logo.png',
    'use_cache' => FALSE,
    'cache_key' => 'laravel-feed-cache-key',
    'cache_duration' => 3600,
    'max_size' => 30,
];
```

In this example, we have configured to serve 30 items in our feed. There is no such rule on many items you should serve, but it is recommended to have your feed sleek. I believe between 20-30 is a good number.

# Laravel Routes for Feed

Let us now define routes for RSS feeds. Here we will define two routes; one for accessing atom feed and other for rss.

```php
// Feeds
Route::get('feed/{type?}', ['as' => 'feed.atom', 'uses' => 'Feed\FeedsController@getFeed']);
```

I personally believe in simplicity, so let us define a method getFeed() in controller and abstract most of the business logic into a service class.

# Feed Controller
```php
<?php

namespace App\Http\Controllers\Web\Feed;

use App\Http\Controllers\Controller;
use App\Services\Feed\FeedBuilder;

class FeedsController extends Controller
{
    private $builder;

    public function __construct(FeedBuilder $builder)
    {
        $this->builder = $builder;
    }

    //We're making atom default type
    public function getFeed($type = "atom")
    {
        if ($type === "rss" || $type === "atom") {
            return $this->builder->render($type);
        }
        
        //If invalid feed requested, redirect home
        return redirect()->home();
    }
}
```

Notice that,

- The feed route defines an optional parameter type. This is used to define what type of feed user want to access.
- Currently we will support only RSS and Atom, and make Atom the default choice.
- If user pass any invalid value for feed type, instead of showing an error page, we will rather redirect to home page.

# FeedBuilder Service

The next part is the real fun is. Here we make use of roumen/feed library APIs to serve latest content in feed.

# Feed Controller
```php
<?php
namespace App\Services\Feed;

use Illuminate\Support\Facades\App;
use App\Models\Post;

class FeedBuilder
{
    private $config;

    public function __construct()
    {
        $this->config = config()->get('feed');
    }

    public function render($type)
    {
        $feed = App::make("feed");		
        if ($this->config['use_cache']) {
            $feed->setCache($this->config['cache_duration'], $this->config['cache_key']);
        }

        if (!$feed->isCached()) {
            $posts = $this->getFeedData();
            $feed->title = $this->config['feed_title'];
            $feed->description = $this->config['feed_description'];
            $feed->logo = $this->config['feed_logo'];
            $feed->link = url('feed');
            $feed->setDateFormat('datetime');
            $feed->lang = 'en';
            $feed->setShortening(true);
            $feed->setTextLimit(250); 

            if (!empty($posts)) {
                $feed->pubdate = $posts[0]->created_at;
                foreach ($posts as $post) {
                    $link = route('post.single', ["id" => $post->id, "slug" => $post->slug]);

                    $author = "";
                    if(!empty($post->user)){
                        $author = $post->user->name;
                    }
                    // set item's title, author, url, pubdate, description, content, enclosure (optional)*
                    $feed->add($post->title, $author, $link, $post->created_at, $post->pitch, $post->about);
                }
            }
        }

        return $feed->render($type);
    }

    /**
     * Creating rss feed with our most recent posts. 
     * The size of the feed is defined in feed.php config.
     *
     * @return mixed
     */
    private function getFeedData()
    {
        $maxSize = $this->config['max_size'];
        $posts = Post::paginate($maxSize);
        return $posts;
    }
}
```

Notice that, if you have multiple feeds for different contents then, you must have to have different cache keys.

Now visit any of the following url and test if your feeds are working.
```
http://localhost:8080/feed
http://localhost:8080/feed/atom
http://localhost:8080/feed/rss
```
