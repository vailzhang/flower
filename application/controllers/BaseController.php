<?php
/**
 * URL generating helper intended to generating valid internal hyperlinks.
 * Important: It requires that a constant named BASE_URL be defined.
 *
 * @param string $actionName Optional action name (defaults to current action)
 * @param string $controllerName Optional controller name (defaults to current controller)
 * @param string $moduleName Optional module name (defaults to current module)
 * @param array $params Optional controller parameters
 * @param array $getParams Optional query parameters
 * @param string $target Optional target
 * @return string The generated URL
 */
class BaseController extends Yaf_Controller_Abstract {
	public function url($actionName = NULL, $controllerName = NULL, $moduleName = NULL, array $params = array(), array $getParams = array(), $target = NULL) {
	
		// Build URL using parts
	
		$urlParts = array();
	
		$urlParts[] = BASE_URL;
	
		if ($moduleName === NULL) $moduleName = $this->getRequest()->getModuleName();
		if ($controllerName === NULL) $controllerName = $this->getRequest()->getControllerName();
		if ($actionName === NULL) $actionName = $this->getRequest()->getActionName();
	
		$moduleName = strtolower($moduleName);
		$controllerName = strtolower($controllerName);
		$actionName = strtolower($actionName);
	
		// Get default module, controller and action names
	
		$Application = Yaf_Application::app();
		$Config = $Application->getConfig();
	
		$Application->getConfig()->application->dispatcher->defaultModule ? $defaultModule = $Application->getConfig()->application->dispatcher->defaultModule : $defaultModule = 'Index';
		$Application->getConfig()->application->dispatcher->defaultController ? $defaultController = $Application->getConfig()->application->dispatcher->defaultController : $defaultController = 'Index';
		$Application->getConfig()->application->dispatcher->defaultAction ? $defaultAction = $Application->getConfig()->application->dispatcher->defaultAction : $defaultAction = 'index';
	
		$defaultModule = strtolower($defaultModule);
		$defaultController = strtolower($defaultController);
		$defaultAction = strtolower($defaultAction);
	
		// Assign module name
	
		if ($moduleName != $defaultModule) {
			$urlParts[] = strtolower(trim($moduleName, '/')); // To validate a module, inspect its presence in $modules = $Application->getModules();
		}
	
		// Assign controller name
	
		if ($actionName != $defaultAction || $controllerName != $defaultController || $moduleName != $defaultModule) {
			$urlParts[] = strtolower(trim($controllerName, '/'));
		}
	
		// Assign action name
	
		if ($actionName != $defaultAction) {
			$urlParts[] = strtolower(trim($actionName, '/'));
		}
	
		// Assign parameters (assumes url parameter pairing)
	
		foreach ($params as $k => $v) {
			if ($v !== NULL) {
				$urlParts[] = $k;
				$urlParts[] = $v;
			}
		}
	
		// Assign get parameters
	
		$getParamsStr = '';
		foreach ($getParams as $k => $v) {
			if (!$getParamsStr) {
				$getParamsStr = '?';
			} else {
				$getParamsStr .= '&';
			}
			$getParamsStr .= rawurlencode($k) . '='. rawurlencode($v);
		}
		if ($getParamsStr) {
			$urlParts[] = $getParamsStr;
		}
	
		// Build the URL
	
		$url = implode('/', $urlParts);
	
		// Assign # target
	
		if ($target !== NULL) {
			$urlParts = explode('#', $url);
			$url = array_shift($urlParts) . '#' . rawurlencode((string) $target);
		}
	
		return $url;
	
	}
}