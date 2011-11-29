<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Menu Builder
 *
 * This class can be used to easily build out a menu in the form
 * of an unordered list. You can add any attributes you'd like to
 * the main list, and each list item has special classes to help
 * you style it.
 *
 * @author   Corey Worrell
 * @homepage http://coreyworrell.com
 * @version  1.1
 * 
 * modification by Wojciech Bruggemann
 */

class Kohana_Menu {

	// Associative array of list items
	private $items = array();
	
	// Associative array of attributes for list
	private $attrs = array();
    
    // Instance of class ACL
    private $acl;
    
    // String - Role of ACL
    private $role;
	
	/**
	 * Creates and returns a new menu object
	 *
	 * @chainable
	 * @param   array   Array of list items (instead of using add() method)
	 * @return  Menu
	 */
	public static function factory(array $items = NULL)
	{
		return new Menu($items);
	}
	
	/**
	 * Constructor, globally sets $items array
	 *
	 * @param   array   Array of list items (instead of using add() method)
	 * @return  Menu
	 */
	public function __construct(array $items = NULL)
	{
		$this->items   = $items;
	}
    
    /** 
     * Add's a object ACL - done by Wouterrr https://github.com/Wouterrr/ACL
     * 
     * @param   ACL   Instance of class ACL
     * @return  Menu
     */
    public function set_acl(ACL $acl)
    {
        $this->acl = $acl;
        
        return $this;
    }
    
    /** 
     * Add's a role of ACL
     * 
     * @param   string   Role of ACL
     * @return  void
     */
    public function set_role($role)
    {
        $this->role = $role;
        
        return $this;
    }
	
	/**
	 * Add's a new list item to the menu
	 *
	 * @chainable
     * @param   string   Controller of link
     * @param   string   Action of link
     * @param   string   Label of link
     * @param   string   Title of link
     * @param   string   Resource of link (ACL)
     * @param   string   Privilege of link (ACL)
	 * @param   Menu     Instance of class that contain children
	 * @return  Menu
	 */
	public function add($controller, $action, $label,
                            $title = NULL, $resource = NULL, $privilege = NULL, Menu $children = NULL)
	{
		/** if not defined $resource and $privilege inheritance from parent item */
        if ($children instanceof self)
        {
            foreach ($children->items as $item)
            {
                if ( ! isset($item->resource))
                    $item->resource = $resource;
                if ( ! isset($item->privilege))
                    $item->privilege = $privilege;
            }  
        }
        
        /** add item */
        $this->items[] = (object) array
		(
            'controller'    => $controller,
            'action'    => $action,
            'label'    => $label,
            'title'    => $title,
            'resource'    => $resource,
            'privilege'    => $privilege,
			'children' => ($children instanceof self) ? $children->items : NULL,
		);
		
		return $this;
	}
	
	/**
	 * Renders the HTML output for the menu
	 *
	 * @param   array   Associative array of html attributes
	 * @param   array   The parent item's array, only used internally
	 * @return  string  HTML unordered list
	 */
	public function render(array $attrs = NULL, array $items = NULL)
	{
		static $i;
		
		$items = empty($items) ? $this->items : $items;
		$attrs = empty($attrs) ? $this->attrs : $attrs;
		
		$i++;
		
		if ($i !== 1)
		{
			$attrs = array();
		}
		
		$attrs['class'] = empty($attrs['class']) ? 'level-'.$i : $attrs['class'].' level-'.$i;
		
		$menu = PHP_EOL.'<ul'.HTML::attributes($attrs).'>'.PHP_EOL;
		
		$is_acl = isset($this->acl) && isset($this->role);
        foreach ($items as $item)
		{
			if ($is_acl AND ! $this->acl->is_allowed($this->role, $item->resource, $item->privilege))
            {
                continue;
            }
            
            //-----------------------------
            
            $has_children = isset($item->children);
			
			$classes = NULL;
			
			if ($has_children)
			{
				$classes[] = 'parent';
			}
			if ($active = $this->active($item))
			{
				$classes[] = $active;
			}
			if ( ! empty($classes))
			{
				$classes = HTML::attributes(array('class' => implode(' ', $classes)));
			}
			
			$menu .= '<li'.$classes.'>'
                    .HTML::anchor(
                            $this->url($item->controller, $item->action),
                            $item->label,
                            isset($item->title) ? array('title' => $item->title) : NULL
                            )
                            ;
			if ($has_children)
			{
				$menu .= $this->render(NULL, $item->children);
			}
			$menu .= '</li>'.PHP_EOL;
		}
		
		$menu .= '</ul>'.PHP_EOL;
		
		$i--;
		
		return $menu;
	}
    
    /**
	 * Create URL
	 *
     * @param   string   Controller
	 * @param   string   Action
	 * @return  string   Returns URL
	 */
    protected function url($controller, $action)
    {
        return $controller.'/'.$action;
    }
	
	/**
	 * Determines if the menu item is part of the current URI
	 *
	 * @param   stdClass   The item to check against
	 * @return  mixed      Returns active class or null
	 */
	private function active(stdClass $item)
	{
		if ($item->controller === Request::current()->controller())
        {
            return
                ($item->action === Request::current()->action()) ? 'active current' : 'active';
        }
        else
            return NULL;
	}
    
	/**
	 * Renders the HTML output for menu without any attributes or active item
	 *
	 * @return   string
	 */
	public function __toString()
	{
		return $this->render();
	}
	
	/**
	 * Easily set list attributes
	 *
	 * @param   mixed   Value to set to
	 * @return  void
	 */
	public function __set($key, $value)
	{
		$this->attrs[$key] = $value;
	}
	
	/**
	 * Get a list attribute
	 *
	 * @return   mixed   Value of key
	 */
	public function __get($key)
	{
		if (isset($this->attrs[$key]))
		{
			return $this->attrs[$key];
		}
	}
	
	/**
	 * Nicely outputs contents of $this->items for debugging info
	 *
	 * @return   string
	 */
	public function debug()
	{
		return Debug::vars($this->items);
	}

}
