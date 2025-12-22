<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManagerInterface;

class SelectController
{
    /**
     * @param iterable<SelectionManagerInterface> $selectionManagers
     */
    public function __construct(
        private readonly iterable $selectionManagers,
    ) {
    }

    public function rowSelectorToggle(Request $request): Response
    {
        $key = $request->query->getString('key', '');
        $manager = $request->query->getString('manager', '');
        $id = $request->query->get('id', null);
        $selected = $request->query->getBoolean('selected', true);

        if (!$manager || !$key || !$id) {
            throw new BadRequestHttpException('Missing key or value or id');
        }

        $selector = $this->getRowsSelector($manager)->getSelection($key);

        if ($selected) {
            $selector->select($id);
        } else {
            $selector->unselect($id);
        }

        return new Response(null, 202);
    }

    public function rowSelectorSelectRange(Request $request): Response
    {
        $key = $request->query->getString('key', '');
        $manager = $request->query->getString('manager', '');
        $ids = json_decode($request->getContent(), true);
        if (JSON_ERROR_NONE !== json_last_error() || null === $ids) {
            throw new BadRequestHttpException('Body is not valid json');
        }
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $selected = $request->query->getBoolean('selected', true);

        $onlyScalar = 0 === count(array_filter($ids, fn ($value) => !is_scalar($value)));

        if (!$manager || !$key) {
            throw new BadRequestHttpException('missing key or value');
        }
        if (!$onlyScalar) {
            throw new BadRequestHttpException('Id variables can be only scalar values');
        }

        $selector = $this->getRowsSelector((string) $manager)->getSelection((string) $key);

        if ($selected) {
            $selector->selectMultiple($ids);
        } else {
            $selector->unselectMultiple($ids);
        }

        return new Response(null, 202);
    }

    public function rowSelectorSelectAll(Request $request): Response
    {
        $key = $request->query->getString('key', '');
        $manager = $request->query->getString('manager', '');
        $selected = $request->query->getBoolean('selected', true);

        if (!$manager || !$key) {
            throw new BadRequestHttpException('missing key or value');
        }

        $selector = $this->getRowsSelector((string) $manager)->getSelection((string) $key);

        if ($selected) {
            $selector->selectAll();
        } else {
            $selector->unselectAll();
        }

        return new Response(null, 202);
    }

    private function getRowsSelector(string $manager): SelectionManagerInterface
    {
        foreach ($this->selectionManagers as $id => $selectionManager) {
            if ($id === $manager) {
                return $selectionManager;
            }
        }
        throw new BadRequestHttpException(sprintf('No selection manager found for manager "%s".', $manager));
    }
}
