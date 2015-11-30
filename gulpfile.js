var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    mix.sass('app.scss', 'public/assets/css/app.css');
    mix.scripts('app.js', 'public/assets/js/app.js');
    mix.copy('node_modules/jquery/dist/jquery.min.js', 'public/assets/js/jquery.js');
    mix.copy('node_modules/font-awesome/fonts', 'public/assets/fonts');
    mix.copy('node_modules/bootstrap-sass/assets/javascripts/bootstrap.min.js', 'public/assets/js/bootstrap.js');
});
