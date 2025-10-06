import React from 'react';

interface MentionTextProps {
    text: string;
    className?: string;
}

export const MentionText: React.FC<MentionTextProps> = ({ text, className }) => {
    // Split text by mentions and create elements
    const parts = text.split(/(@[a-zA-Z0-9._-]+)/g);
    
    return (
        <span className={className}>
            {parts.map((part, index) => {
                if (part.startsWith('@')) {
                    return (
                        <span
                            key={index}
                            className="mention font-medium text-blue-600 bg-blue-50 px-1 py-0.5 rounded text-sm"
                        >
                            {part}
                        </span>
                    );
                }
                return part;
            })}
        </span>
    );
};