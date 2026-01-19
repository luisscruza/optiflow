import React from 'react';

interface MentionTextProps {
    text: string;
    className?: string;
}

export const MentionText: React.FC<MentionTextProps> = ({ text, className }) => {
    // Split text by mentions, supporting both @username and @[Full Name]
    // This regex matches both @[Full Name] and @username patterns
    const parts = text.split(/(@\[[^\]]+\]|@[a-zA-Z0-9._-]+)/g);

    return (
        <span className={className}>
            {parts.map((part, index) => {
                // Check if it's a mention (either @[Name] or @username)
                if (part.startsWith('@')) {
                    // Remove brackets if present: @[Full Name] -> @Full Name
                    const displayText = part.replace(/^\@\[([^\]]+)\]$/, '@$1');

                    return (
                        <span key={index} className="mention rounded bg-blue-50 px-1 py-0.5 text-sm font-medium text-blue-600">
                            {displayText}
                        </span>
                    );
                }
                return part;
            })}
        </span>
    );
};
