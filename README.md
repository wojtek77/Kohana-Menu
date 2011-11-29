# Menu for Kohana

## Instalation

1. Copy the directory "menu" to directory "modules" in project Kohana

2. Add module in bootstrap:

**application/bootstrap.php**

	'menu'       => MODPATH.'menu',        // Menu
	
	// optional
	'acl'        => MODPATH.'acl',        // ACL - https://github.com/Wouterrr/ACL
	'seo'        => MODPATH.'seo',        // SEO friendly url - https://github.com/wojtek77/Kohana-SEO

## Controller

### Example without ACL

	$this->template->menu =
            Menu::factory()
                ->add('welcome', 'index', 'Welcome', 'Title - welcome', NULL, NULL,
                        Menu::factory()
                            ->add('welcome', 'index', 'Welcome')
                        )
                ->add('test', 'index', 'Test', 'Title - test', NULL, NULL,
                        Menu::factory()
                            ->add('test', 'index', 'Test')
                        );

### Example with ACL

You must have previously defined a class ACL, example:

**application/classes/acl.php**

	class ACL extends ACL_Core {
		
		const ROLE_GUEST = 'guest';
		const ROLE_USER = 'user';
		const ROLE_ADMIN = 'admin';

		const RESOURCE_SHOP = 'shop';
		const RESOURCE_PANEL = 'panel';

		const PRIVILEGE_VIEW = 'view';
		const PRIVILEGE_DELETE = 'delete';

		public function __construct()
		{

			$this->add_role(self::ROLE_GUEST);
			$this->add_role(self::ROLE_USER, self::ROLE_GUEST); // "user" extends "guest" 
			$this->add_role(self::ROLE_ADMIN);

			$this->add_resource(self::RESOURCE_SHOP);
			$this->add_resource(self::RESOURCE_PANEL);

			$this->allow(self::ROLE_GUEST, self::RESOURCE_SHOP, self::PRIVILEGE_VIEW);
			$this->allow(self::ROLE_USER, self::RESOURCE_SHOP, self::PRIVILEGE_DELETE);
			$this->allow(self::ROLE_USER, self::RESOURCE_PANEL, self::PRIVILEGE_VIEW);
			$this->allow(self::ROLE_ADMIN, null, null, new Model_AssertIP());
		}

		/**
		 * singleton
		 * @return ACL
		 */
		static public function getInstance()
		{
			if (!isset(self::$_instance))
			{
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		static private $_instance;

	}

and class Model_AssertIP used by class ACL:

**application/classes/model/assertip.php**

	class Model_AssertIP implements Acl_Assert_Interface {
		
		public function assert(Acl $acl,
							   $role = null,
							   $resource = null,
							   $privilege = null)
		{
			return $this->_is_assert_IP($_SERVER['REMOTE_ADDR']);
		}
		
		protected function _is_assert_IP($ip)
		{
			return $ip === '127.0.0.1';
		}
	}

Create menu:

	$this->template->menu =
            Menu::factory()
                ->add('welcome', 'index', 'Welcome', 'Title - welcome',
                        ACL::RESOURCE_SHOP, ACL::PRIVILEGE_VIEW,
                        Menu::factory()
                            ->add('welcome', 'index', 'Welcome', NULL,
                                    ACL::RESOURCE_SHOP, ACL::PRIVILEGE_DELETE
                                    )
                        )
                ->add('test', 'index', 'Test', 'Title - test',
                        ACL::RESOURCE_PANEL, ACL::PRIVILEGE_VIEW,
                        Menu::factory()
                            ->add('test', 'index', 'Test', NULL,
                                    ACL::RESOURCE_PANEL, ACL::PRIVILEGE_DELETE
                                    )
                        )
                ->set_acl(ACL::getInstance())
                ->set_role(ACL::ROLE_ADMIN);

## View
	<?php echo $menu ?>

## Example for use SEO

1. You have to install a module Kohana-SEO https://github.com/wojtek77/Kohana-SEO

2. You must override the method "url" of class Menu:

**application/classes/menu.php**

	class Menu extends Kohana_Menu {
		
		protected function url($controller, $action)
		{
			return SEO::friendly_url($controller, $action);
		}
	}