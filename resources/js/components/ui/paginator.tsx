import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { type PaginatedResponse } from '@/types';

interface PaginatorProps {
    data: Pick<PaginatedResponse, 'current_page' | 'last_page' | 'from' | 'to' | 'total' | 'links' | 'prev_page_url' | 'next_page_url'>;
    className?: string;
}

export function Paginator({ data, className }: PaginatorProps) {
    const {
        current_page: currentPage,
        last_page: lastPage,
        from,
        to,
        total,
        links,
        prev_page_url: prevPageUrl,
        next_page_url: nextPageUrl,
    } = data;

    if (lastPage <= 1) return null;

    // Filter out the "Previous" and "Next" links from the main links array
    const pageLinks = links.filter(
        (link) => !link.label.includes('Previous') && !link.label.includes('Next')
    );

    return (
        <div className={cn('flex items-center justify-between', className)}>
            {/* Results info */}
            <div className="text-sm text-gray-600 dark:text-gray-400">
                Mostrando {from.toLocaleString()} a {to.toLocaleString()} de{' '}
                {total.toLocaleString()} resultados
            </div>

            {/* Pagination controls */}
            <div className="flex items-center gap-1">
                {/* Previous button */}
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!prevPageUrl}
                    asChild={!!prevPageUrl}
                >
                    {prevPageUrl ? (
                        <Link prefetch preserveScroll  href={prevPageUrl}>
                            <ChevronLeft className="h-4 w-4 mr-1" />
                            Anterior
                        </Link>
                    ) : (
                        <>
                            <ChevronLeft className="h-4 w-4 mr-1" />
                            Anterior
                        </>
                    )}
                </Button>

                {/* Page numbers */}
                <div className="flex items-center gap-1">
                    {pageLinks.map((link, index) => {
                        if (link.label === '...') {
                            return (
                                <div
                                    key={`ellipsis-${index}`}
                                    className="px-3 py-1 text-sm text-gray-500"
                                >
                                    <MoreHorizontal className="h-4 w-4" />
                                </div>
                            );
                        }

                        return (
                            <Button
                                key={link.page || index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                className="min-w-[40px]"
                                asChild={!!link.url}
                                disabled={!link.url}
                            >
                                {link.url ? (
                                    <Link prefetch preserveScroll href={link.url}>{link.label}</Link>
                                ) : (
                                    <span>{link.label}</span>
                                )}
                            </Button>
                        );
                    })}
                </div>

                {/* Next button */}
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!nextPageUrl}
                    asChild={!!nextPageUrl}
                >
                    {nextPageUrl ? (
                        <Link prefetch preserveScroll href={nextPageUrl}>
                            Siguiente
                            <ChevronRight className="h-4 w-4 ml-1" />
                        </Link>
                    ) : (
                        <>
                            Siguiente
                            <ChevronRight className="h-4 w-4 ml-1" />
                        </>
                    )}
                </Button>
            </div>
        </div>
    );
}