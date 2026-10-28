import { FlaskConical } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

/**
 * Marks a 🧪 POC screen (ARCHITECTURE.md §5): everything on it is sample data
 * from the frontend, nothing is read from or saved to the database.
 */
export function PreviewBanner() {
    return (
        <Alert className="alert-soft alert-info">
            <FlaskConical />
            <AlertTitle>Preview — sample data</AlertTitle>
            <AlertDescription>
                This screen is a proof of concept. The figures and rows are
                invented, and nothing here is saved.
            </AlertDescription>
        </Alert>
    );
}
