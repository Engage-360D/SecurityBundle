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
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Engage360d\Bundle\RestBundle\Controller\RestController;
use Engage360d\Bundle\SecurityBundle\Engage360dSecurityEvents;
use Engage360d\Bundle\SecurityBundle\Event\FormEvent;

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
     *  requirements="\d+",
     *  default="1",
     *  description="Confirm registration."
     * )
     * 
     * @return User.
     */
    public function postUsersAction($confirmation)
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
            return new JsonResponse($this->getErrorMessages($form), 400);
        }

        if ($confirmation == '1') {
          $dispatcher = $this->container->get('event_dispatcher');

          $event = new FormEvent($form);
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
            if ($user->getId() !== $id) {
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
            return new JsonResponse($this->getErrorMessages($form), 400);
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
