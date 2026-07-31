const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

const numberFormatter = new Intl.NumberFormat('id-ID');

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    return dateFormatter.format(new Date(value));
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    return dateTimeFormatter.format(new Date(value));
}

export function formatNumber(value: number): string {
    return numberFormatter.format(value);
}

export function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB', 'TB'];
    let value = bytes;
    let unit = 'B';

    for (const next of units) {
        if (value < 1024) {
            break;
        }

        value /= 1024;
        unit = next;
    }

    return `${value.toFixed(1)} ${unit}`;
}
