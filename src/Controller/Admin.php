<?php
namespace Brave\TimerBoard\Controller;

use Brave\TimerBoard\Entity\Event;
use Brave\TimerBoard\Entity\System;
use Brave\TimerBoard\View;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class Admin extends BaseController
{
    /** @noinspection PhpUnusedParameterInspection */
    public function index(ServerRequestInterface $request, Response $response, array $args): ResponseInterface
    {
        $view = new View(ROOT_DIR . '/views/admin.php');
        $view->addVar('head', $this->head);
        $view->addVar('foot', $this->foot);
        $view->addVar('systemNames', $this->getSystemNames());
        $view->addVar('event', $this->getEvent($args));

        $response->write($view->getContent());

        return $response;
    }

    public function save(Request $request, Response $response, array $args): ResponseInterface
    {
        $event = $this->getEvent($args);

        $event->setSystem($this->getSystem($request));
        $event->priority = (string) $request->getParam('priority');
        $event->type = (string) $request->getParam('type');
        $event->structure = (string) $request->getParam('structure');
        $event->standing = (string) $request->getParam('standing');
        $event->eventTime = $this->getDateFromRequest($request);
        $event->result = (string) $request->getParam('result');
        $event->notes = (string) $request->getParam('notes');
        $event->updatedAt = date_create();
        $event->updatedBy = $this->security->getAuthorizedName();

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $response->withRedirect('/');
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function delete(Request $request, Response $response, array $args): ResponseInterface
    {
        $event = $this->getEvent($args);
        if ($event) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();
        }

        return $response->withRedirect('/');
    }

    /**
     * @return string[]
     */
    private function getSystemNames()
    {
        /* @var $systems System[] */
        $systems = $this->systemRepository->findAll();

        $systemNames = [];
        foreach ($systems as $system) {
            $systemNames[] = $system->name;
        }

        return $systemNames;
    }

    private function getEvent(array $args): Event
    {
        $editId = isset($args['id']) && (int) $args['id'] > 0 ? (int) $args['id'] : 0;
        if ($editId > 0) {
            $event = $this->eventRepository->find($editId);
        }
        if (! isset($event)) {
            $event = new Event();
        }

        return $event;
    }

    private function getDateFromRequest(Request $request): DateTime
    {
        $days = trim((string) $request->getParam('days'));
        $hours = trim((string) $request->getParam('hours'));
        $minutes = trim((string) $request->getParam('minutes'));
        if ($days . $hours . $minutes !== '') {
            $dateStr = '';
            $dateStr .= $days !== ''    ? " +$days day" : '';
            $dateStr .= $hours !== ''   ? " +$hours hour" : '';
            $dateStr .= $minutes !== '' ? " +$minutes minute" : '';
        } else {
            $dateStr = (string) $request->getParam('date') . ' ' . (string) $request->getParam('time');
        }

        return date_create($dateStr);
    }

    private function getSystem(Request $request)
    {
        $name = (string) $request->getParam('system');

        return $this->systemRepository->find($name);
    }
}
