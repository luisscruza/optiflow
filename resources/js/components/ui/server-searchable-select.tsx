"use client"

import * as React from "react"
import { Check, ChevronsUpDown, Loader2 } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Command, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { cn } from "@/lib/utils"

export interface ServerSearchableSelectOption {
  value: string
  label: string
  disabled?: boolean
}

interface ServerSearchableSelectProps {
  options: ServerSearchableSelectOption[]
  value?: string
  selectedLabel?: string
  onValueChange?: (value: string) => void
  onSearchChange?: (query: string) => void
  placeholder?: string
  searchPlaceholder?: string
  searchPromptText?: string
  emptyText?: string
  loadingText?: string
  minSearchLength?: number
  noEmptyAction?: React.ReactNode
  footerAction?: React.ReactNode
  disabled?: boolean
  isLoading?: boolean
  className?: string
  triggerClassName?: string
  contentClassName?: string
  labelClassName?: string
  width?: number | string
}

function ServerSearchableSelect({
  options,
  value,
  selectedLabel,
  onValueChange,
  onSearchChange,
  placeholder = "Select option...",
  searchPlaceholder = "Search...",
  searchPromptText,
  emptyText = "No option found.",
  loadingText = "Searching...",
  minSearchLength = 2,
  noEmptyAction,
  footerAction,
  disabled = false,
  isLoading = false,
  className,
  triggerClassName,
  contentClassName,
  labelClassName,
  width,
}: ServerSearchableSelectProps) {
  const [open, setOpen] = React.useState(false)
  const [searchQuery, setSearchQuery] = React.useState("")

  const selectedOption = options.find((option) => option.value === value)
  const hasSearchQuery = searchQuery.trim().length >= minSearchLength
  const promptText =
    searchPromptText ??
    (minSearchLength > 1
      ? `Escribe al menos ${minSearchLength} caracteres para buscar.`
      : "Escribe para buscar.")

  React.useEffect(() => {
    if (open) {
      return
    }

    setSearchQuery("")
  }, [open])

  return (
    <div className={cn("relative", className)}>
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            role="combobox"
            aria-expanded={open}
            disabled={disabled}
            className={cn("w-full justify-between", triggerClassName)}
          >
            <span className={cn("truncate", labelClassName)}>
              {selectedOption ? selectedOption.label : selectedLabel ?? placeholder}
            </span>
            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
          </Button>
        </PopoverTrigger>
        <PopoverContent
          className={cn("w-full p-0", contentClassName)}
          style={{ width: width ?? "var(--radix-popover-trigger-width)" }}
        >
          <Command className="min-w-48" shouldFilter={false}>
            <CommandInput
              value={searchQuery}
              onValueChange={(nextQuery) => {
                setSearchQuery(nextQuery)
                onSearchChange?.(nextQuery)
              }}
              placeholder={searchPlaceholder}
            />
            <CommandList>
              {!hasSearchQuery ? (
                <div className="px-3 py-6 text-center text-sm text-gray-500">
                  {promptText}
                </div>
              ) : isLoading ? (
                <div className="flex items-center justify-center gap-2 px-3 py-6 text-sm text-gray-500">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  <span>{loadingText}</span>
                </div>
              ) : options.length === 0 ? (
                <div className="py-6 text-center text-sm">
                  <div className="mb-3 text-gray-500">{emptyText}</div>
                  {noEmptyAction && (
                    <div className="flex justify-center">
                      {noEmptyAction}
                    </div>
                  )}
                </div>
              ) : (
                <CommandGroup>
                  {options.map((option) => (
                    <CommandItem
                      key={option.value}
                      value={option.label}
                      keywords={[option.label, option.value]}
                      disabled={option.disabled}
                      onSelect={() => {
                        onValueChange?.(option.value)
                        setOpen(false)
                      }}
                    >
                      <Check
                        className={cn(
                          "mr-2 h-4 w-4",
                          value === option.value ? "opacity-100" : "opacity-0"
                        )}
                      />
                      {option.label}
                    </CommandItem>
                  ))}
                </CommandGroup>
              )}
              {footerAction && (
                <div className="border-t border-gray-200 p-2">
                  {footerAction}
                </div>
              )}
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>
    </div>
  )
}

export { ServerSearchableSelect }
