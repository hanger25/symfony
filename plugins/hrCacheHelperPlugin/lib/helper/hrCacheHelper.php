<?php

/*
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * hrCacheHelper
 *
 * @package    symfony
 * @subpackage plugin
 * @author     hanger25 <hanger35@gmail.com>
 * @version    0.1.0
 */

class hrCache{
  static public $cache_array = array();
}
function hr_is_cached($name, $lifeTime = 86400, $internalUri = NULL)
{
  if (!sfConfig::get('sf_cache'))
  {
    return false;
  }

  $context = sfContext::getInstance();
  $cache   = $context->getViewCacheManager();
  $controller   = $context->getController();

  if (sfConfig::get('symfony.cache.started'))
  {
    throw new sfCacheException('Cache already started.');
  }

  // base sfViewCacheManger::start()
  // replaced source : $data = $cache->start($name, $lifeTime);
  { // fix
    if( $internalUri == null ) $internalUri = sfContext::getInstance()->getRouting()->getCurrentInternalUri();
   
    list($route_name, $params) = $controller->convertUrlStringToParameters($internalUri);
    $is_cacheable = $cache->isActionCacheable( $params['module'], $params['action'] );
    $cache->addCache($params['module'], $params['action'], array('withLayout' => false, 'lifeTime' => $lifeTime, 'clientLifeTime' => $lifeTime, 'vary' => $vary));
    
    // get data from cache if available
    $r_internalUri = $internalUri.(strpos($internalUri, '?') ? '&' : '?').'_sf_cache_key='.$name;
    $cache_key = $cache->generateCacheKey( $r_internalUri );
    if( isset( hrCache::$cache_array[$cache_key] ) ){
      $data = hrCache::$cache_array[$cache_key];
    } else {
      $data = $cache->get($r_internalUri);
      hrCache::$cache_array[$cache_key] = $data;
    }
    
    // action cache disabled
    if( !$is_cacheable ){
      $cache->addCache( $params['module'], $params['action'], array() );
    }
  }
  //var_dump($params, ($data !== null));exit;

  return ($data !== null);
}

function hr_cache($name, $lifeTime = 86400, $internalUri = NULL)
{
  if (!sfConfig::get('sf_cache'))
  {
    return null;
  }

  $context = sfContext::getInstance();
  $cache   = $context->getViewCacheManager();
  $controller   = $context->getController();

  if (sfConfig::get('symfony.cache.started'))
  {
    throw new sfCacheException('Cache already started.');
  }

  // base sfViewCacheManger::start()
  // replaced source : $data = $cache->start($name, $lifeTime);
  { // fix
    if( $internalUri == null ) $internalUri = sfContext::getInstance()->getRouting()->getCurrentInternalUri();

    // add cache config to cache manager
    list($route_name, $params) = $controller->convertUrlStringToParameters($internalUri);
    $is_cacheable = $cache->isActionCacheable( $params['module'], $params['action'] );
    $cache->addCache($params['module'], $params['action'], array('withLayout' => false, 'lifeTime' => $lifeTime, 'clientLifeTime' => $lifeTime, 'vary' => $vary));
    // get data from cache if available
    $r_internalUri = $internalUri.(strpos($internalUri, '?') ? '&' : '?').'_sf_cache_key='.$name;
    $cache_key = $cache->generateCacheKey( $r_internalUri );

    if( isset( hrCache::$cache_array[$cache_key] ) ){
      $data = hrCache::$cache_array[$cache_key];
    } else {
      $data = $cache->get($r_internalUri);
    }

    if ($data === null) {
      ob_start();
      ob_implicit_flush(0);

      $data = null;
    }
  } 

  if (null === $data)
  {
    sfConfig::set('symfony.cache.started', true);
    sfConfig::set('symfony.cache.current_name', $name);
    sfConfig::set('symfony.cache.internal_uri', $internalUri);
    sfConfig::set('symfony.cache.action', $params['action']);
    sfConfig::set('symfony.cache.module', $params['module']);
    sfConfig::set('symfony.cache.action_is_cacheable', $is_cacheable);

    return false;
  }
  else
  {
    echo $data;

    // action cache disabled
    if( !$is_cacheable ){
      $cache->addCache( $params['module'], $params['action'], array() );
    }
    
    return true;
  }
}

function hr_cache_save()
{
  $context = sfContext::getInstance();
  $cache   = $context->getViewCacheManager();
  
  if (!sfConfig::get('sf_cache')) return null;

  if (!sfConfig::get('symfony.cache.started')) throw new sfCacheException('Cache not started.');
  
  $name = sfConfig::get('symfony.cache.current_name', '');

  // base sfViewCacheManger::stop()
  // replaced source -> $data = $context->getViewCacheManager()->stop($name);
  { // fix
    $data = ob_get_clean();
    
    // save content to cache
    $internalUri = sfConfig::get('symfony.cache.internal_uri', '');
    try
    {
      $cache->set($data, $internalUri.(strpos($internalUri, '?') ? '&' : '?').'_sf_cache_key='.$name);
    }
    catch (Exception $e)
    {
    }

    // action cache disabled
    if( !sfConfig::get('symfony.cache.action_is_cacheable', false) ){
      $cache->addCache( sfConfig::get('symfony.cache.module',null),
                        sfConfig::get('symfony.cache.action',null),
                        array() );
    }
  }

  sfConfig::set('symfony.cache.started', false);
  sfConfig::set('symfony.cache.current_name', null);
  sfConfig::set('symfony.cache.internal_uri', null);
  sfConfig::set('symfony.cache.module', null);
  sfConfig::set('symfony.cache.action', null);
  sfConfig::set('symfony.cache.action_is_cacheable', null);

  echo $data;
}

