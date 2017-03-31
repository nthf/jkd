<?php

namespace Jkd\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//        $isMobile = isMobile();
//        // 域名跳转判断
//        if (isset($_SERVER['HTTP_HOST'])) {
//            $domain = $_SERVER['HTTP_HOST'];
//            $isMobileDomain = is_string($domain) && 0 === strpos($domain, 'm.');
//
//            // 当手机设备访问PC站时跳手机站域名
//            if ($isMobile) {
//                if (!$isMobileDomain) {
//                    header('Location:http://m.' . env('APP_DOMAIN') . $_SERVER['REQUEST_URI']);
//                    exit();
//                }
//            } 
//            // 当电脑设备访问手机站是跳PC站域名
//            else {
//                if ($isMobileDomain) {
//                    header('Location:http://www.' . env('APP_DOMAIN') . $_SERVER['REQUEST_URI']);
//                    exit();
//                }
//            }
//        }
        
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
        $isMobileDomain = is_string($domain) && 0 === strpos($domain, 'm.');
        // 设置模版目录
        if ($isMobileDomain) { // 手机版
            $this->loadViewsFrom(resource_path('themes/mobile'), 'themes');
        } else { // 电脑版
            $this->loadViewsFrom(resource_path('themes/pc'), 'themes');
        }
        // 设置后台视图目录
        $this->loadViewsFrom(resource_path('views/admin'), 'admin');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
