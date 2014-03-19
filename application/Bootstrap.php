<?php 
class Bootstrap extends Yaf_Bootstrap_Abstract{

        public function _initConfig() {
                $config = Yaf_Application::app()->getConfig();
                Yaf_Registry::set("config", $config);
        }

        public function _initDefaultName(Yaf_Dispatcher $dispatcher) {
                $dispatcher->setDefaultModule("Index")->setDefaultController("Index")->setDefaultAction("index");
        }
        //注册布局插件
        public function _initLayout(Yaf_Dispatcher $dispatcher){
        	$layout = new LayoutPlugin('layout.phtml');
        	Yaf_Registry::set('layout', $layout);
        	$dispatcher->registerPlugin($layout);
        }
        
        //注册db插件
//         public function _initMongodb(Yaf_Dispatcher $dispatcher){
//         	$mongodb = new Mongo('news');
//         	Yaf_Registry::set('mongodb', $mongodb);
//         	$dispatcher->registerPlugin($mongodb);
//         }
        //路由规则
        //         public function _initRoute(Yaf_Dispatcher $dispatcher) {
        //         	$router = Yaf_Dispatcher::getInstance()->getRouter();
        //         	$router->addConfig(Yaf_Registry::get("config")->routes);
        //         }
}
