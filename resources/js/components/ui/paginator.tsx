import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Link, router } from '@inertiajs/react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { type PaginatedResponse } from '@/types';

interface PaginatorProps {
    data: Pick<PaginatedResponse, 'current_page' | 'last_page' | 'from' | 'to' | 'total' | 'links' | 'prev_page_url' | 'next_page_url' | 'per_page'>;
    perPageOptions?: number[];
    className?: string;
}

export function Paginator({ data, perPageOptions = [10, 15, 30, 50, 100], className }: PaginatorProps) {
    const {
        current_page: currentPage,
        last_page: lastPage,
        from,
        to,
        total,
        prev_page_url: prevPageUrl,
        next_page_url: nextPageUrl,
        per_page: perPage = 30,
    } = data;

    const [pageInput, setPageInput] = useState(currentPage.toString());

    if (total === 0) return null;

    const handlePerPageChange = (value: string) => {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', value);
        url.searchParams.set('page', '1'); // Reset to first page
        router.visit(url.pathname + url.search, { preserveState: true, preserveScroll: true });
    };

    const handlePageInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        if (value === '' || /^\d+$/.test(value)) {
            setPageInput(value);
        }
    };

    const handlePageInputSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const page = parseInt(pageInput, 10);
        if (page >= 1 && page <= lastPage && page !== currentPage) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page.toString());
            router.visit(url.pathname + url.search, { preserveState: true, preserveScroll: true });
        } else {
            setPageInput(currentPage.toString());
        }
    };

    const handlePageInputBlur = () => {
        const page = parseInt(pageInput, 10);
        if (!page || page < 1 || page > lastPage) {
            setPageInput(currentPage.toString());
        }
    };

    return (
        <div className={cn('flex items-center justify-between rounded-lg border bg-card px-4 py-3 text-sm', className)}>
            {/* Left side - Items per page */}
            <div className="flex items-center gap-2">
                <span className="text-muted-foreground">Items por página:</span>
                <Select value={perPage.toString()} onValueChange={handlePerPageChange}>
                    <SelectTrigger className="h-9 w-[70px]">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {perPageOptions.map((option) => (
                            <SelectItem key={option} value={option.toString()}>
                                {option}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {/* Center - Range display */}
            <div className="text-muted-foreground">
                {from}-{to} de {total > 1000 ? `${Math.floor(total / 1000)}k+` : total}
            </div>

            {/* Right side - Page navigation */}
            <div className="flex items-center gap-2">
                <span className="text-muted-foreground">Página</span>
                <form onSubmit={handlePageInputSubmit} className="flex items-center gap-2">
                    <Input
                        type="text"
                        value={pageInput}
                        onChange={handlePageInputChange}
                        onBlur={handlePageInputBlur}
                        className="h-9 w-[60px] text-center"
                    />
                    <span className="text-muted-foreground">{lastPage}</span>
                </form>

                {/* Navigation buttons */}
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9"
                    disabled={!prevPageUrl}
                    asChild={!!prevPageUrl}
                >
                    {prevPageUrl ? (
                        <Link prefetch preserveScroll href={prevPageUrl}>
                            <ChevronLeft className="h-4 w-4" />
                        </Link>
                    ) : (
                        <ChevronLeft className="h-4 w-4" />
                    )}
                </Button>

                <Button
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9"
                    disabled={!nextPageUrl}
                    asChild={!!nextPageUrl}
                >
                    {nextPageUrl ? (
                        <Link prefetch preserveScroll href={nextPageUrl}>
                            <ChevronRight className="h-4 w-4" />
                        </Link>
                    ) : (
                        <ChevronRight className="h-4 w-4" />
                    )}
                </Button>
            </div>
        </div>
    );
}