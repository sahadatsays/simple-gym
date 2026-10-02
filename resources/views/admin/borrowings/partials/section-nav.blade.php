<nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Borrowing sections">
    <a
        href="{{ route('admin.borrowings.index') }}"
        @class(['btn', 'btn-primary' => request()->routeIs('admin.borrowings.index'), 'btn-light' => ! request()->routeIs('admin.borrowings.index')])
    >
        <i class="bi bi-cash-coin me-1" aria-hidden="true"></i>
        Borrowings
    </a>
    <a
        href="{{ route('admin.borrowings.repayments.index') }}"
        @class(['btn', 'btn-primary' => request()->routeIs('admin.borrowings.repayments.index'), 'btn-light' => ! request()->routeIs('admin.borrowings.repayments.index')])
    >
        <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
        Repayments
    </a>
</nav>
