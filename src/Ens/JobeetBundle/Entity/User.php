<?php
namespace Ens\JobeetBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;

class User implements UserInterface
{
	/**
	 * 
	 * @var integer
	 */
	private $id;
	
	/**
	 * 
	 * @var string
	 */
	private $username;
	
	/**
	 * 
	 * @var string
	 */
	private $password;
	
	/**
	 * 
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * 
	 * @param string $username
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Symfony\Component\Security\Core\User\UserInterface::getUsername()
	 */
	public function getUsername()
	{
		return $this->username;
	}
	
	/**
	 * 
	 * @param string $password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Symfony\Component\Security\Core\User\UserInterface::getPassword()
	 */
	public function getPassword()
	{
		return $this->password;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Symfony\Component\Security\Core\User\UserInterface::getRoles()
	 */
	public function getRoles()
	{
		return array('ROLE_ADMIN');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Symfony\Component\Security\Core\User\UserInterface::getSalt()
	 */
	public function getSalt()
	{
		return null;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Symfony\Component\Security\Core\User\UserInterface::eraseCredentials()
	 */
	public function eraseCredentials()
	{
		
	}
	
	/**
	 * 
	 * @param UserInterface $user
	 * @return boolean
	 */
	public function equals(UserInterface $user)
	{
		return $user->getUsername() == $this->getUsername();
	}
}