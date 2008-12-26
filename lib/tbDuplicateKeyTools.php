<?php

class tbDuplicateKeyTools
{
  static public function validate($className)
  {
    $className = strtolower(substr($className, 0, 1)) . 
      substr($className, 1);
    if (self::hasFlash('duplicate-key'))
    {
      $key = $className . '{' . self::getFlash('duplicate-key') . '}';
      sfContext::getInstance()->getRequest()->setError(
        $className . '{' . self::getFlash('duplicate-key') . '}',
        sfConfig::get('tbDuplicateKeyTools_error', 'Already exists'));
      // Shouldn't be necessary, but without this the error keeps repeating
      self::setFlash('duplicate-key', null);
      return false;
    }
    return true;
  }
  static public function examine($module, $e)
  {
    // MySQL duplicate key errors become friendly validation messages
    $message = $e->getMessage();
    if (preg_match("/Native Error\: Duplicate entry .* for key (\d+)/i", 
      $e->getMessage(), $matches))
    {
      $keyIndex = $matches[1];
      // But what column is this about? Ah, that's the fun part: let's ask
      // MySQL!
      $con = Propel::getConnection('propel');
      $stmt = $con->createStatement();
      $query = "SHOW KEYS FROM " . VenuePeer::TABLE_NAME;
      $rs = $stmt->executeQuery($query, ResultSet::FETCHMODE_ASSOC);
      $count = 1;
      while ($rs->next())
      {
        if ($count == $keyIndex)
        {
          $column = $rs->get('Column_name');
          break;
        }
        $count++;
      }
      if (isset($column))
      {
        self::setFlash('duplicate-key', $column);
        sfContext::getInstance()->getController()->forward($module, 'edit');
        throw new sfStopException();
      }
    }
    else
    {
      throw $e;
    }
  }
  static private function hasFlash($k)
  {
    $user = sfContext::getInstance()->getUser();
    return $user->hasAttribute($k, "symfony/flash");
  }
  static private function setFlash($k, $v)
  {
    $user = sfContext::getInstance()->getUser();
    return $user->setAttribute($k, $v, "symfony/flash");
  }
  static private function getFlash($k, $d = false)
  {
    $user = sfContext::getInstance()->getUser();
    return $user->getAttribute($k, $d, "symfony/flash");
  }
}
