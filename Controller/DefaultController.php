<?php

namespace EMS\LocalUserBundle\Controller;


use EMS\CoreBundle\Form\Field\ObjectPickerType;
use EMS\CoreBundle\Form\Field\SubmitEmsType;
use EMS\CoreBundle\Service\UserService;
use EMS\LocalUserBundle\Entity\User;
use FOS\UserBundle\Util\LegacyFormHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
	
	/**
	 * @return UserService
	 */
	protected function getUserService()
	{
		return $this->get('ems.service.user');
	}
	
    /**
     *
     * @Route("/user/add", name="user.add")
     */
    public function addUserAction(Request $request)
    {
    	$user = new User();
    	$form = $this->createFormBuilder($user)
	    	->add('username', null, array('label' => 'form.username', 'translation_domain' => 'FOSUserBundle'))
	    	->add('email', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\EmailType'), array('label' => 'form.email', 'translation_domain' => 'FOSUserBundle'))
	    	->add('plainPassword', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\RepeatedType'), array(
	    			'type' => LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\PasswordType'),
	    			'options' => array('translation_domain' => 'FOSUserBundle'),
	    			'first_options' => array('label' => 'form.password'),
	    			'second_options' => array('label' => 'form.password_confirmation'),
	    			'invalid_message' => 'fos_user.password.mismatch',));
    	if ($circleObject = $this->container->getParameter('ems_core.circles_object')) {
    		$form->add('circles', ObjectPickerType::class, [
    				'multiple' => TRUE,
    				'type' => $circleObject,
    				'dynamicLoading' => false
    
    		]);
    	}
    	
    	$form = $form->add('roles', ChoiceType::class, array('choices' => $this->getUserService()->getExistingRoles(),
    			'label' => 'Roles',
    			'expanded' => true,
    			'multiple' => true,
    			'mapped' => true,))
    			->add ( 'create', SubmitEmsType::class, [
    					'attr' => [
    							'class' => 'btn-primary btn-sm '
    					],
    					'icon' => 'fa fa-plus',
    			] )
    			->getForm();
    
    			$form->handleRequest($request);
    
    			if ($form->isSubmitted() && $form->isValid()) {
    				/** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
    				$userManager = $this->get('fos_user.user_manager');
    					
    				$continue = TRUE;
    				$continue = $this->userExist($user, 'add', $form);
    
    				if ($continue) {
    					$user->setEnabled(TRUE);
    					$userManager->updateUser($user);
    					$this->addFlash(
    							'notice',
    							'User created!'
    							);
    					return $this->redirectToRoute('ems.user.index');
    				}
    			}
    
    			return $this->render('EMSCoreBundle:user:add.html.twig', array(
    					'form' => $form->createView()
    			));
    }
    
    
    /**
     * Test if email or username exist return on add or edit Form
     */
    private function userExist ($user, $action, $form) {
    	/** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
    	$userManager = $this->get('fos_user.user_manager');
    	$exists = array('email' => $userManager->findUserByEmail($user->getEmail()), 'username' => $userManager->findUserByUsername($user->getUsername()));
    	$messages = array('email' => 'User email already exist!', 'username' => 'Username already exist!');
    	foreach ($exists as $key => $value) {
    		if ($value instanceof User) {
    			if ($action == 'add' or ($action == 'edit' and $value->getId() != $user->getId()))
    			{
    				$this->addFlash(
    						'error',
    						$messages[$key]
    						);
    				return FALSE;
    			}
    		}
    	}
    	return TRUE;
    }
    
}
