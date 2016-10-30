# Todo List


## Backend libraries

- [Silex](http://silex.sensiolabs.org/)
- [Doctrine](http://www.doctrine-project.org/)
- [Symfony seciurity](http://symfony.com/doc/2.8/security.html)

## Frontend libraries

- [jQuery](https://jquery.com/)
- [todomvc boilerplate](https://github.com/tastejs/todomvc)

### Requirements

- Web server nginx
- php5.5+
- php5-xcache

### How to install

- clone this repo
- make bootstrap.sh executable
```
$ chmod +x bootstrap.sh
```
- execute bootstrap.sh
```
$ ./bootstrap.sh
```
- create config App/config/config.php
- create database
```
mysql -uUSER -pPASSWORD MYDBNAME < sql/dump.sql
```
- nginx config
```
server {
    server_name todo.dev;
    root /path/to/web;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass 127.0.0.1:9000;

        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS off;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/todo_error.log;
    access_log /var/log/nginx/todo_access.log;
}

```
