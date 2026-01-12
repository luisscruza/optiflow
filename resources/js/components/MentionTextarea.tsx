import React, { useState, useRef, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import type { SharedData, User } from '@/types';

interface MentionTextareaProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    rows?: number;
    disabled?: boolean;
    className?: string;
}

export const MentionTextarea: React.FC<MentionTextareaProps> = ({
    value,
    onChange,
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
            const filteredUsers = users.filter(user =>
                user.name.toLowerCase().includes(query.toLowerCase()) ||
                user.email.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 5); // Limit to 5 suggestions

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
                setSelectedIndex(prev => Math.min(prev + 1, suggestions.length - 1));
                break;
            case 'ArrowUp':
                e.preventDefault();
                setSelectedIndex(prev => Math.max(prev - 1, 0));
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
        const newValue = `${beforeMention}@${user.name} ${afterMention}`;
        
        onChange(newValue);
        setShowSuggestions(false);

        // Focus back to textarea and set cursor position
        setTimeout(() => {
            if (textareaRef.current) {
                const newCursorPos = mentionStart + user.name.length + 2; // +2 for "@" and space
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
            .map(word => word[0])
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
                className={cn("resize-none", className)}
                maxLength={240}
            />
            
            {showSuggestions && (
                <div
                    ref={suggestionsRef}
                    className="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto"
                >
                    {suggestions.map((user, index) => (
                        <div
                            key={user.id}
                            className={cn(
                                "flex items-center gap-3 px-3 py-2 cursor-pointer",
                                index === selectedIndex
                                    ? "bg-blue-50 text-blue-900"
                                    : "hover:bg-gray-50"
                            )}
                            onClick={() => handleSuggestionClick(user)}
                        >
                            <div className="flex-shrink-0">
                                <div className="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center text-xs font-medium">
                                    {getInitials(user.name)}
                                </div>
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="text-sm font-medium text-gray-900 truncate">
                                    {user.name}
                                </div>
                                <div className="text-xs text-gray-500 truncate">
                                    {user.email}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
            <div className="absolute bottom-1 right-2 text-xs text-gray-500">
            {value.length}/240
            </div>
        </div>
    );
};