import * as React from 'react';
import PhoneInputBase from 'react-phone-number-input/input';
import type { Props } from 'react-phone-number-input/input';

import { cn } from '@/lib/utils';

const PhoneInputComponent = React.forwardRef<HTMLInputElement, React.ComponentProps<'input'>>(({ className, ...props }, ref) => (
    <input
        ref={ref}
        type="tel"
        data-slot="input"
        className={cn(
            'border-input file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
            'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
            'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
            className,
        )}
        {...props}
    />
));
PhoneInputComponent.displayName = 'PhoneInputComponent';

type PhoneInputProps = Omit<Props<React.ComponentProps<'input'>>, 'inputComponent'>;

export default function PhoneInput({ defaultCountry = 'DO', ...props }: PhoneInputProps) {
    return <PhoneInputBase defaultCountry={defaultCountry} inputComponent={PhoneInputComponent} {...props} />;
}
