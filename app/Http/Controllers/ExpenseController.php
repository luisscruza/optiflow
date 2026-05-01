<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateExpenseAction;
use App\Actions\DeleteExpenseAction;
use App\Actions\UpdateExpenseAction;
use App\Enums\ExpenseStatus;
use App\Enums\Permission;
use App\Exceptions\ReportableActionException;
use App\Http\Requests\CreateExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\User;
use App\Models\Workspace;
use App\Support\ContactSearch;
use App\Tables\ExpensesTable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class ExpenseController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ExpensesView), 403);

        $table = ExpensesTable::make($request);

        if ($user->can(Permission::ViewAllLocations)) {
            $table->query(Expense::query()->withoutWorkspaceScope());
        }

        return Inertia::render('expenses/index', [
            'expenses' => $table,
        ]);
    }

    public function create(Request $request, ContactSearch $contactSearch, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ExpensesCreate), 403);

        /** @var Workspace|null $currentWorkspace */
        $currentWorkspace = Context::get('workspace');

        return Inertia::render('expenses/create', [
            'currentWorkspace' => $currentWorkspace,
            'availableWorkspaces' => $this->availableWorkspaces($user),
            'supplierSearchResults' => Inertia::optional(
                fn (): array => $contactSearch->searchSuppliers((string) $request->string('supplier_search'))
            ),
            'statuses' => ExpenseStatus::options(),
        ]);
    }

    public function store(CreateExpenseRequest $request, CreateExpenseAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ExpensesCreate), 403);

        try {
            $expense = $action->handle($user, $request->validated());
        } catch (ReportableActionException $exception) {
            return redirect()->route('expenses.create')
                ->withErrors($exception->errors() !== [] ? $exception->errors() : ['workspace_id' => $exception->getMessage()]);
        }

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Gasto creado correctamente.');
    }

    public function show(string $expense, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ExpensesView), 403);

        $expenseModel = $this->findExpense($user, $expense);
        $expenseModel->load(['contact', 'workspace', 'media']);

        return Inertia::render('expenses/show', [
            'expense' => $expenseModel,
            'receivedDocumentId' => $expenseModel->easyfactu_received_document_id ?: (request()->string('receivedDocument')->toString() ?: null),
        ]);
    }

    public function edit(Request $request, string $expense, ContactSearch $contactSearch, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ExpensesEdit), 403);

        $expenseModel = $this->findExpense($user, $expense);
        $expenseModel->load(['contact', 'workspace', 'media']);

        /** @var Workspace|null $currentWorkspace */
        $currentWorkspace = Context::get('workspace');

        return Inertia::render('expenses/edit', [
            'expense' => $expenseModel,
            'currentWorkspace' => $currentWorkspace,
            'availableWorkspaces' => $this->availableWorkspaces($user),
            'supplierSearchResults' => Inertia::optional(
                fn (): array => $contactSearch->searchSuppliers((string) $request->string('supplier_search'))
            ),
            'statuses' => ExpenseStatus::options(),
            'initialSupplier' => $contactSearch->findSupplierById($expenseModel->contact_id),
        ]);
    }

    public function update(UpdateExpenseRequest $request, string $expense, UpdateExpenseAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ExpensesEdit), 403);

        $expenseModel = $this->findExpense($user, $expense);

        try {
            $updatedExpense = $action->handle($user, $expenseModel, $request->validated());
        } catch (ReportableActionException $exception) {
            return redirect()->route('expenses.edit', $expenseModel->id)
                ->withErrors($exception->errors() !== [] ? $exception->errors() : ['workspace_id' => $exception->getMessage()]);
        }

        return redirect()->route('expenses.show', $updatedExpense)
            ->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(string $expense, DeleteExpenseAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ExpensesDelete), 403);

        $expenseModel = $this->findExpense($user, $expense);

        $action->handle($expenseModel);

        return redirect()->route('expenses.index')
            ->with('success', 'Gasto eliminado correctamente.');
    }

    /**
     * @return Collection<int, Workspace>
     */
    private function availableWorkspaces(User $user): Collection
    {
        $workspacesQuery = Workspace::query()->select(['id', 'name', 'slug'])->orderBy('name');

        if (! $user->can(Permission::ViewAllLocations)) {
            $workspacesQuery->where('id', $user->current_workspace_id);
        }

        return $workspacesQuery->get();
    }

    private function findExpense(User $user, string $expenseId): Expense
    {
        $query = Expense::query();

        if ($user->can(Permission::ViewAllLocations)) {
            $query->withoutWorkspaceScope();
        }

        return $query->findOrFail($expenseId);
    }
}
