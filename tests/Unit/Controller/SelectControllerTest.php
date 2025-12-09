<?php

namespace Tito10047\PersistentStateBundle\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tito10047\PersistentStateBundle\Controller\SelectController;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionInterface;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManagerInterface;

class SelectControllerTest extends TestCase {

	private function createControllerWithManager(string $id, string $key, ?SelectionInterface $selection): SelectController {

		if (!$selection){
			$selection = $this->createMock(SelectionInterface::class);
		}

		/** @var SelectionManagerInterface&MockObject $manager */
		$manager = $this->createMock(SelectionManagerInterface::class);
		$manager->expects($this->once())
			->method('getSelection')
			->with($key)
			->willReturn($selection);
		// Controller accepts iterable keyed by manager id
		return new SelectController([$id => $manager]);
	}

	public function testRowSelectorToggleSelectsWhenSelectedTrue(): void {
		$key       = 'orders';
		$managerId = 'default';
		$itemId    = 123;

		/** @var SelectionInterface&MockObject $selection */
		$selection = $this->createMock(SelectionInterface::class);
		$selection->expects($this->once())->method('select')->with($itemId);
		$selection->expects($this->never())->method('unselect');

		$controller = $this->createControllerWithManager($managerId, $key, $selection);

		$request = new Request(
			query: [
				'key'      => $key,
				'manager'  => $managerId,
				'id'       => $itemId,
				'selected' => '1',
			]
		);

		$response = $controller->rowSelectorToggle($request);
		$this->assertSame(202, $response->getStatusCode());
	}

	public function testRowSelectorToggleUnselectsWhenSelectedFalse(): void {
		$key       = 'orders';
		$managerId = 'default';
		$itemId    = 987;

		/** @var SelectionInterface&MockObject $selection */
		$selection = $this->createMock(SelectionInterface::class);
		$selection->expects($this->once())->method('unselect')->with($itemId);
		$selection->expects($this->never())->method('select');

		$controller = $this->createControllerWithManager($managerId, $key, $selection);

		$request = new Request(
			query: [
				'key'      => $key,
				'manager'  => $managerId,
				'id'       => $itemId,
				'selected' => '0',
			]
		);

		$response = $controller->rowSelectorToggle($request);
		$this->assertSame(202, $response->getStatusCode());
	}

	public function testRowSelectorToggleThrowsOnMissingKeyOrManager(): void {
		$controller = new SelectController([]);

		$this->expectException(BadRequestHttpException::class);
		$request = new Request(query: ['key' => 'k']);
		$controller->rowSelectorToggle($request);
	}

	public function testRowSelectorSelectRangeSelectsMultiple(): void {
		$key       = 'products';
		$managerId = 'array';
		$ids       = [1, '2', 3];

		/** @var SelectionInterface&MockObject $selection */
		$selection = $this->createMock(SelectionInterface::class);
		$selection->expects($this->once())->method('selectMultiple')->with($ids);
		$selection->expects($this->never())->method('unselectMultiple');

		$controller = $this->createControllerWithManager($managerId, $key, $selection);

		$request = new Request(
			query: [
				'key'      => $key,
				'manager'  => $managerId,
				'selected' => '1',
			],
			content: json_encode($ids),

		);

		$response = $controller->rowSelectorSelectRange($request);
		$this->assertSame(202, $response->getStatusCode());
	}

	public function testRowSelectorSelectRangeUnselectsMultipleWhenSelectedFalse(): void {
		$key       = 'products';
		$managerId = 'array';
		$ids       = [10, 11, 12];

		/** @var SelectionInterface&MockObject $selection */
		$selection = $this->createMock(SelectionInterface::class);
		$selection->expects($this->once())->method('unselectMultiple')->with($ids);
		$selection->expects($this->never())->method('selectMultiple');


		$controller = $this->createControllerWithManager($managerId, $key, $selection);

		$request = new Request(
			query: [
				'key'      => $key,
				'manager'  => $managerId,
				'selected' => '0',
			],
			content: json_encode($ids)
		);

		$response = $controller->rowSelectorSelectRange($request);
		$this->assertSame(202, $response->getStatusCode());
	}

	public function testRowSelectorSelectRangeThrowsOnNonScalarIds(): void {
		// For invalid (non-scalar) IDs, the controller validates input before
		// calling manager->getSelection(). Therefore, we must not set an
		// expectation that getSelection() will be invoked here.
		/** @var SelectionManagerInterface&MockObject $manager */
		$manager = $this->createMock(SelectionManagerInterface::class);
		$controller = new SelectController(['any' => $manager]);

		$this->expectException(BadRequestHttpException::class);

		$request = new Request(
			query: [
				'key'     => 'k',
				'manager' => 'any',
			],
			request: [
				'id' => [1, ['nested' => 'x']],
			]
		);

		$controller->rowSelectorSelectRange($request);
	}

	public function testRowSelectorToggleThrowsOnUnknownManager(): void {
		$knownManager = $this->createMock(SelectionManagerInterface::class);
		$controller   = new SelectController(['known' => $knownManager]);

		$this->expectException(BadRequestHttpException::class);

		$request = new Request(query: [
			'key'      => 'k',
			'manager'  => 'unknown',
			'id'       => 1,
			'selected' => '1',
		]);

		$controller->rowSelectorToggle($request);
	}

	public function testRowSelectorSelectRangeThrowsOnUnknownManager(): void {
		$knownManager = $this->createMock(SelectionManagerInterface::class);
		$controller   = new SelectController(['known' => $knownManager]);

		$this->expectException(BadRequestHttpException::class);

		$request = new Request(
			query: [
				'key'      => 'k',
				'manager'  => 'unknown',
				'selected' => '1',
			],
			request: [
				'id' => [1, 2],
			]
		);

		$controller->rowSelectorSelectRange($request);
	}

	public function testRowSelectorSelectRangeWrapsSingleScalarId(): void {
		$key       = 'products';
		$managerId = 'array';
		$singleId  = 42;

		/** @var SelectionInterface&MockObject $selection */
		$selection = $this->createMock(SelectionInterface::class);
		$selection->expects($this->once())->method('selectMultiple')->with([$singleId]);
		$selection->expects($this->never())->method('unselectMultiple');

		$controller = $this->createControllerWithManager($managerId, $key, $selection);

		$request = new Request(
			query: [
				'key'      => $key,
				'manager'  => $managerId,
				'selected' => '1',
			],
			content: json_encode($singleId)
		);

		$response = $controller->rowSelectorSelectRange($request);
		$this->assertSame(202, $response->getStatusCode());
	}

	public function testRowSelectorSelectRangeHandlesEmptyIdsArray(): void {
		$key       = 'products';
		$managerId = 'array';

		/** @var SelectionInterface&MockObject $selection */
		$selection = $this->createMock(SelectionInterface::class);
		$selection->expects($this->once())->method('selectMultiple')->with([]);
		$selection->expects($this->never())->method('unselectMultiple');

		$controller = $this->createControllerWithManager($managerId, $key, $selection);

		$request = new Request(
			query: [
				'key'      => $key,
				'manager'  => $managerId,
				'selected' => '1',
			],
			content: "[]"
		);

		$response = $controller->rowSelectorSelectRange($request);
		$this->assertSame(202, $response->getStatusCode());
	}

	public function testRowSelectorSelectRangeThrowsOnMissingKeyOrManager(): void {
		$controller = new SelectController([]);

		$this->expectException(BadRequestHttpException::class);

		$request = new Request(
			query: [
				// missing 'manager'
				'key' => 'k',
			],
			request: [
				'id' => [1, 2, 3],
			]
		);

		$controller->rowSelectorSelectRange($request);
	}

	public function testRowSelectorSelectAllThrowsOnMissingKeyOrManager(): void {
		$controller = new SelectController([]);

		$this->expectException(BadRequestHttpException::class);

		$request = new Request(query: [
			// missing 'manager'
			'key' => 'abc',
		]);

		$controller->rowSelectorSelectAll($request);
	}

	public function testRowSelectorSelectAllSelectsAll(): void {
		$key       = 'customers';
		$managerId = 'default';

		/** @var SelectionInterface&MockObject $selection */
		$selection = $this->createMock(SelectionInterface::class);
		$selection->expects($this->once())->method('selectAll');
		$selection->expects($this->never())->method('unselectAll');

		$controller = $this->createControllerWithManager($managerId, $key, $selection);

		$request = new Request(query: [
			'key'      => $key,
			'manager'  => $managerId,
			'selected' => '1',
		]);

		$response = $controller->rowSelectorSelectAll($request);
		$this->assertSame(202, $response->getStatusCode());
	}

	public function testRowSelectorSelectAllUnselectsAllWhenSelectedFalse(): void {
		$key       = 'customers';
		$managerId = 'default';

		/** @var SelectionInterface&MockObject $selection */
		$selection = $this->createMock(SelectionInterface::class);
		$selection->expects($this->once())->method('unselectAll');
		$selection->expects($this->never())->method('selectAll');


		$controller = $this->createControllerWithManager($managerId, $key, $selection);

		$request = new Request(query: [
			'key'      => $key,
			'manager'  => $managerId,
			'selected' => '0',
		]);

		$response = $controller->rowSelectorSelectAll($request);
		$this->assertSame(202, $response->getStatusCode());
	}

	public function testThrowsWhenManagerNotFound(): void {
		$controller = new SelectController(['known' => $this->createMock(SelectionManagerInterface::class)]);

		$this->expectException(BadRequestHttpException::class);
		$controller->rowSelectorSelectAll(new Request(query: [
			'key'     => 'abc',
			'manager' => 'unknown',
		]));
	}
}
