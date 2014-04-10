<?php

/**
 * This file is part of the Engage360d package bundles.
 *
 */

namespace Engage360d\Bundle\SecurityBundle\Controller\Api;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Form\Exception\InvalidPropertyPathException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Engage360d\Bundle\RestBundle\Controller\RestController;
use Engage360d\Bundle\SecurityBundle\Engage360dSecurityEvents;
use Engage360d\Bundle\SecurityBundle\Event\FormEvent;
use Engage360d\Bundle\SecurityBundle\Event\UserEvent;

/**
 * Rest controller для работы с пользователями (users).
 *
 * @author Andrey Linko <AndreyLinko@gmail.com>
 */
class UserController extends RestController
{
    /**
     *
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Получение профайла пользователя"
     * )
     * 
     * @return Array User
     */
    public function getUsersMeAction()
    {
        if (false === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }

        $user = $this->container->get('security.context')->getToken()->getUser();

        if (!$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $user;
    }

    /**
     *
     * @Post("/users/reset")
     * @ApiDoc(
     *  resource=true,
     *  description="Сброс пароля пользователя"
     * )
     * 
     * @return Array User
     */
    public function postUsersResetAction(Request $request)
    {
        $username = $request->request->get('username');

        $userManager = $this->container
            ->get('engage360d_rest.entity_manager.factory')
            ->getEntityManagerByRoute($this->getRequest()->get('_route'));
        
        $user = $userManager
            ->findUserByUsernameOrEmail($username);

        if (null === $user) {
            return new JsonResponse(array('error' => 'User not found'), 500);
        }

        if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return new JsonResponse(array('error' => 'Password already requested'), 500);
        }

        if (null === $user->getConfirmationToken()) {
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        $dispatcher = $this->container->get('event_dispatcher');

        $event = new UserEvent($user);
        $dispatcher->dispatch(Engage360dSecurityEvents::RESETTING_USER_PASSWORD, $event);

        $user->setPasswordRequestedAt(new \DateTime());
        $userManager->updateUser($user);

        return array();
    }

    /**
     *
     * @Post("/users/reset/{token}")
     * @ApiDoc(
     *  resource=true,
     *  description="Изменение пароля пользователя"
     * )
     * 
     * @return Array User
     */
    public function putUsersResetAction(Request $request, $token)
    {
        $formFactory = $this->container->get('engage360d_rest.form.factory');
        $userManager = $this->container
            ->get('engage360d_rest.entity_manager.factory')
            ->getEntityManagerByRoute($this->getRequest()->get('_route'));

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            return new JsonResponse(array('error' => 'Token not found'), 500);
        }

        $form = $formFactory->createFormByRoute(
            $this->getRequest()->get('_route')
        );

        $form->setData($user);

        $form->bind($this->getRequest()->request->all());

        if (!$form->isValid()) {
            return new JsonResponse($this->getErrorMessages($form), 500);
        }

        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);
        $user->setEnabled(true);

        $userManager->updateUser($user);
        
        $dispatcher = $this->container->get('event_dispatcher');

        $event = new UserEvent($user);
        $dispatcher->dispatch(Engage360dSecurityEvents::RESET_USER_PASSWORD_SUCCESS, $event);
        
        return array();
    }

    /**
     *
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Получение списка пользователей.",
     *  filters={
     *      {"name"="limit", "dataType"="integer"},
     *      {"name"="page", "dataType"="integer"}
     *  }
     * )
     * 
     * @return Array Users
     */
    public function getUsersAction()
    {
        if (false === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')
          && false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        $limit = $this->container->get('request')->get('limit') ?: 25;
        $page = $this->container->get('request')->get('page') ?: 1;

        return $this->container
            ->get('engage360d_rest.entity_manager.factory')
            ->getEntityManagerByRoute($this->getRequest()->get('_route'))
            ->getPage($page, $limit);
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Создание нового пользователя.",
     *  formType="FOS\SecurityBundle\Form\Type\PostUserFormType"
     * )
     *
     * @QueryParam(
     *  name="confirmation",
     *  default=true,
     *  description="Confirm registration."
     * )
     * 
     * @QueryParam(
     *  name="authenticate",
     *  default=true,
     *  description="Authenticate user."
     * )
     * 
     * @return User.
     */
    public function postUsersAction($confirmation = true, $authenticate = true)
    {
        $formFactory = $this->container->get('engage360d_rest.form.factory');
        $userManager = $this->container
            ->get('engage360d_rest.entity_manager.factory')
            ->getEntityManagerByRoute($this->getRequest()->get('_route'));

        $user = $userManager->createUser();
        $user->setEnabled(false);

        $form = $formFactory->createFormByRoute(
            $this->getRequest()->get('_route')
        );

        $form->setData($user);

        $form->bind($this->getRequest()->request->all());

        if (!$form->isValid()) {
            return new JsonResponse($this->getErrorMessages($form), 500);
        }

        if ($confirmation == 'true') {
          $dispatcher = $this->container->get('event_dispatcher');

          $user = $form->getData();

          if (null === $user->getConfirmationToken()) {
              $tokenGenerator = $this->container->get('fos_user.util.token_generator');
              $user->setConfirmationToken($tokenGenerator->generateToken());
          }

          $event = new UserEvent($user, $authenticate == 'true');
          $dispatcher->dispatch(Engage360dSecurityEvents::REGISTRATION_SUCCESS, $event);
        }

        $userManager->updateUser($user);
        return $user;
    }

    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Получение детальной информации пользователя по id."
     * )
     * @param string $id User id property.
     *
     * @return User.
     */
    public function getUserAction($id)
    {
        if (false === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')
          && false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        return $this->container
            ->get('engage360d_security.manager.user')
            ->findById($id);
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Редактирование пользователя.",
     *  formType="FOS\SecurityBundle\Form\Type\PutUserFormType"
     * )
     * @param string $id User id property.
     *
     * @return User.
     */
    public function putUsersAction($id)
    {
        if (false === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }

        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $user = $this->container->get('security.context')->getToken()->getUser();
            if ($user->getId() != $id) {
                throw new AccessDeniedException();
            }
        }

        $formFactory = $this->container->get('engage360d_rest.form.factory');
        $userManager = $this->container
            ->get('engage360d_rest.entity_manager.factory')
            ->getEntityManagerByRoute($this->getRequest()->get('_route'));

        $user = $userManager
            ->findById($id, true);

        $form = $formFactory->createFormByRoute(
            $this->getRequest()->get('_route')
        );

        $form->setData($user);

        $form->bind($this->getRequest()->request->all());

        if (!$form->isValid()) {
            return new JsonResponse($this->getErrorMessages($form), 500);
        }

        $userManager->updateUser($user);
        return $user;
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="Удаление пользователя"
     * )
     * @param string $id User id property.
     *
     * @return Array.
     */
    public function deleteUserAction($id)
    {
        if (false === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')
          && false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        $userManager = $this->container
            ->get('engage360d_rest.entity_manager.factory')
            ->getEntityManagerByRoute($this->getRequest()->get('_route'));

        $user = $userManager->findById($id);
        $userManager->deleteUser($user);

        return array();
    }
}
