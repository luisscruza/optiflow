import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import type { User } from '@/types';
import { usePage } from '@inertiajs/react';
import React, { useEffect, useRef, useState } from 'react';

interface MentionTextareaProps {
    value: string;
    onChange: (value: string) => void;
    onMentionChange?: (mentionedUserIds: number[]) => void;
    placeholder?: string;
    rows?: number;
    disabled?: boolean;
    className?: string;
}

export const MentionTextarea: React.FC<MentionTextareaProps> = ({
    value,
    onChange,
    onMentionChange,
    placeholder,
    rows = 3,
    disabled = false,
    className,
}) => {
    const { workspaceUsers = [] } = usePage().props as any;
    const users = workspaceUsers as User[];
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [suggestions, setSuggestions] = useState<User[]>([]);
    const [selectedIndex, setSelectedIndex] = useState(0);
    const [mentionQuery, setMentionQuery] = useState('');
    const [mentionStart, setMentionStart] = useState(0);
    const [mentionedUserIds, setMentionedUserIds] = useState<number[]>([]);
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const suggestionsRef = useRef<HTMLDivElement>(null);

    const handleInputChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        const newValue = e.target.value;
        const cursorPosition = e.target.selectionStart;

        onChange(newValue);

        // Check for mention trigger
        const textBeforeCursor = newValue.slice(0, cursorPosition);
        const mentionMatch = textBeforeCursor.match(/@([a-zA-Z0-9._-]*)$/);

        if (mentionMatch) {
            const query = mentionMatch[1];
            setMentionQuery(query);
            setMentionStart(cursorPosition - mentionMatch[0].length);

            // Filter users based on query
            const filteredUsers = users
                .filter((user) => user.name.toLowerCase().includes(query.toLowerCase()) || user.email.toLowerCase().includes(query.toLowerCase()))
                .slice(0, 5); // Limit to 5 suggestions

            setSuggestions(filteredUsers);
            setShowSuggestions(filteredUsers.length > 0);
            setSelectedIndex(0);
        } else {
            setShowSuggestions(false);
            setSuggestions([]);
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (!showSuggestions) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                setSelectedIndex((prev) => Math.min(prev + 1, suggestions.length - 1));
                break;
            case 'ArrowUp':
                e.preventDefault();
                setSelectedIndex((prev) => Math.max(prev - 1, 0));
                break;
            case 'Enter':
            case 'Tab':
                e.preventDefault();
                if (suggestions[selectedIndex]) {
                    insertMention(suggestions[selectedIndex]);
                }
                break;
            case 'Escape':
                setShowSuggestions(false);
                break;
        }
    };

    const insertMention = (user: User) => {
        const beforeMention = value.slice(0, mentionStart);
        const afterMention = value.slice(textareaRef.current?.selectionStart || 0);

        // Use brackets for names with spaces
        const mentionText = user.name.includes(' ') ? `@[${user.name}]` : `@${user.name}`;
        const newValue = `${beforeMention}${mentionText} ${afterMention}`;

        // Track mentioned user ID
        const newMentionedIds = [...mentionedUserIds, user.id];
        setMentionedUserIds(newMentionedIds);
        onMentionChange?.(newMentionedIds);

        onChange(newValue);
        setShowSuggestions(false);

        // Focus back to textarea and set cursor position
        setTimeout(() => {
            if (textareaRef.current) {
                const newCursorPos = mentionStart + mentionText.length + 1; // +1 for space
                textareaRef.current.focus();
                textareaRef.current.setSelectionRange(newCursorPos, newCursorPos);
            }
        }, 0);
    };

    const handleSuggestionClick = (user: User) => {
        insertMention(user);
    };

    // Close suggestions when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (suggestionsRef.current && !suggestionsRef.current.contains(event.target as Node)) {
                setShowSuggestions(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    return (
        <div className="relative">
            <Textarea
                ref={textareaRef}
                value={value}
                onChange={handleInputChange}
                onKeyDown={handleKeyDown}
                placeholder={placeholder}
                rows={rows}
                disabled={disabled}
                className={cn('resize-none', className)}
                maxLength={240}
            />

            {showSuggestions && (
                <div
                    ref={suggestionsRef}
                    className="absolute z-10 mt-1 max-h-40 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg"
                >
                    {suggestions.map((user, index) => (
                        <div
                            key={user.id}
                            className={cn(
                                'flex cursor-pointer items-center gap-3 px-3 py-2',
                                index === selectedIndex ? 'bg-blue-50 text-blue-900' : 'hover:bg-gray-50',
                            )}
                            onClick={() => handleSuggestionClick(user)}
                        >
                            <div className="flex-shrink-0">
                                <div className="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-xs font-medium">
                                    {getInitials(user.name)}
                                </div>
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="truncate text-sm font-medium text-gray-900">{user.name}</div>
                                <div className="truncate text-xs text-gray-500">{user.email}</div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
            <div className="absolute right-2 bottom-1 text-xs text-gray-500">{value.length}/240</div>
        </div>
    );
};
