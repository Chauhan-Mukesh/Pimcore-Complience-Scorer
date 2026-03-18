import React from 'react';
import type { MissingField, SeverityLevel } from './api';

/**
 * Maps a SeverityLevel to a colour and icon for display.
 */
const SEVERITY_META: Record<SeverityLevel, { colour: string; icon: string; label: string }> = {
    error:   { colour: '#ef4444', icon: '●', label: 'Error' },
    warning: { colour: '#f59e0b', icon: '●', label: 'Warning' },
    info:    { colour: '#3b82f6', icon: '●', label: 'Info' },
};

/**
 * Pimcore Studio deep-link helper.
 * Attempts to use the host application's `pimcore.helpers.openElement()` if available,
 * otherwise falls back to a hash-based URL navigation.
 */
function openPimcoreObject(objectId: number, fieldPath: string): void {
    type PimcoreGlobal = {
        pimcore?: {
            helpers?: {
                openElement?: (id: number, type: string) => void;
            };
        };
    };

    const win = window as Window & PimcoreGlobal;
    if (typeof win.pimcore?.helpers?.openElement === 'function') {
        win.pimcore.helpers.openElement(objectId, 'object');
    } else {
        window.location.hash = `?object/${objectId}/field/${encodeURIComponent(fieldPath)}`;
    }
}

interface MissingFieldListProps {
    missingFields: MissingField[];
    objectId: number;
}

/**
 * Renders the list of missing fields grouped by tab, with severity colour-coding,
 * dimension labels, optional error messages, and jump-to-field links.
 */
export const MissingFieldList: React.FC<MissingFieldListProps> = ({ missingFields, objectId }) => {
    if (missingFields.length === 0) {
        return (
            <p style={styles.allGood}>
                ✅ All required fields are complete!
            </p>
        );
    }

    // Sort: errors first, then warnings, then info; within each group by weight descending.
    const severityOrder: SeverityLevel[] = ['error', 'warning', 'info'];
    const sorted = [...missingFields].sort((a, b) => {
        const si = severityOrder.indexOf(a.severity) - severityOrder.indexOf(b.severity);
        if (si !== 0) return si;
        return b.weight - a.weight;
    });

    // Group by tabHint.
    const grouped = sorted.reduce<Record<string, MissingField[]>>((acc, field) => {
        const tab = field.tabHint ?? 'General';
        acc[tab] ??= [];
        acc[tab].push(field);
        return acc;
    }, {});

    return (
        <div>
            {Object.entries(grouped).map(([tabName, fields]) => (
                <div key={tabName} style={styles.group}>
                    <h4 style={styles.groupTitle}>{tabName}</h4>
                    <ul style={styles.list}>
                        {fields.map((field) => {
                            const sev = SEVERITY_META[field.severity] ?? SEVERITY_META.error;
                            return (
                                <li key={field.fieldPath} style={styles.listItem}>
                                    <span
                                        style={{ ...styles.severityDot, color: sev.colour }}
                                        title={sev.label}
                                    >
                                        {sev.icon}
                                    </span>
                                    <span style={styles.labelCol}>
                                        <span style={styles.label}>{field.label}</span>
                                        {field.errorMessage !== null && (
                                            <span style={styles.errorMsg}>{field.errorMessage}</span>
                                        )}
                                    </span>
                                    <span style={{ ...styles.weight, color: sev.colour }}>
                                        -{field.weight.toFixed(0)}%
                                    </span>
                                    <a
                                        href={`#?object/${objectId}/field/${encodeURIComponent(field.fieldPath)}`}
                                        style={styles.jumpLink}
                                        title={`Jump to ${field.label}`}
                                        onClick={(e) => {
                                            e.preventDefault();
                                            openPimcoreObject(objectId, field.fieldPath);
                                        }}
                                    >
                                        ↗
                                    </a>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            ))}
        </div>
    );
};

const styles: Record<string, React.CSSProperties> = {
    allGood: {
        color: '#22c55e',
        fontSize: '0.875rem',
        padding: '8px 0',
    },
    group: {
        marginBottom: '12px',
    },
    groupTitle: {
        fontSize: '0.75rem',
        fontWeight: '600',
        color: '#6b7280',
        textTransform: 'uppercase',
        letterSpacing: '0.05em',
        margin: '0 0 6px',
    },
    list: {
        listStyle: 'none',
        margin: 0,
        padding: 0,
    },
    listItem: {
        display: 'flex',
        alignItems: 'flex-start',
        gap: '6px',
        padding: '5px 0',
        borderBottom: '1px solid #f3f4f6',
        fontSize: '0.8125rem',
    },
    severityDot: {
        fontSize: '0.6rem',
        lineHeight: '1.4',
        flexShrink: 0,
        paddingTop: '2px',
    },
    labelCol: {
        flex: 1,
        display: 'flex',
        flexDirection: 'column',
        gap: '1px',
    },
    label: {
        color: '#374151',
    },
    errorMsg: {
        color: '#9ca3af',
        fontSize: '0.6875rem',
        fontStyle: 'italic',
    },
    weight: {
        fontWeight: '600',
        fontSize: '0.75rem',
        flexShrink: 0,
    },
    jumpLink: {
        color: '#3b82f6',
        textDecoration: 'none',
        fontSize: '0.875rem',
        flexShrink: 0,
        lineHeight: '1.4',
    },
};


/**
 * Pimcore Studio deep-link helper.
 * Attempts to use the host application's `pimcore.helpers.openElement()` if available,
 * otherwise falls back to a hash-based URL navigation.
 */
function openPimcoreObject(objectId: number, fieldPath: string): void {
    type PimcoreGlobal = {
        pimcore?: {
            helpers?: {
                openElement?: (id: number, type: string) => void;
            };
        };
    };

    const win = window as Window & PimcoreGlobal;
    if (typeof win.pimcore?.helpers?.openElement === 'function') {
        win.pimcore.helpers.openElement(objectId, 'object');
    } else {
        window.location.hash = `?object/${objectId}/field/${encodeURIComponent(fieldPath)}`;
    }
}

interface MissingFieldListProps {
    missingFields: MissingField[];
    objectId: number;
}

/**
 * Renders the list of missing fields with their weights and optional jump-to-field links.
 *
 * Jump links use pimcore.helpers.openElement() deep-link convention:
 *   #?object/{objectId}/field/{fieldPath}
 */
export const MissingFieldList: React.FC<MissingFieldListProps> = ({ missingFields, objectId }) => {
    if (missingFields.length === 0) {
        return (
            <p style={styles.allGood}>
                ✅ All required fields are complete!
            </p>
        );
    }

    // Group by tabHint.
    const grouped = missingFields.reduce<Record<string, MissingField[]>>((acc, field) => {
        const tab = field.tabHint ?? 'General';
        if (!acc[tab]) {
            acc[tab] = [];
        }
        acc[tab].push(field);
        return acc;
    }, {});

    return (
        <div>
            {Object.entries(grouped).map(([tabName, fields]) => (
                <div key={tabName} style={styles.group}>
                    <h4 style={styles.groupTitle}>{tabName}</h4>
                    <ul style={styles.list}>
                        {fields.map((field) => (
                            <li key={field.fieldPath} style={styles.listItem}>
                                <span style={styles.label}>{field.label}</span>
                                <span style={styles.weight}>-{field.weight.toFixed(0)}%</span>
                                <a
                                    href={`#?object/${objectId}/field/${encodeURIComponent(field.fieldPath)}`}
                                    style={styles.jumpLink}
                                    title={`Jump to ${field.label}`}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        openPimcoreObject(objectId, field.fieldPath);
                                    }}
                                >
                                    Jump ↗
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            ))}
        </div>
    );
};

const styles: Record<string, React.CSSProperties> = {
    allGood: {
        color: '#22c55e',
        fontSize: '0.875rem',
        padding: '8px 0',
    },
    group: {
        marginBottom: '12px',
    },
    groupTitle: {
        fontSize: '0.75rem',
        fontWeight: '600',
        color: '#6b7280',
        textTransform: 'uppercase',
        letterSpacing: '0.05em',
        margin: '0 0 6px',
    },
    list: {
        listStyle: 'none',
        margin: 0,
        padding: 0,
    },
    listItem: {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        padding: '4px 0',
        borderBottom: '1px solid #f3f4f6',
        fontSize: '0.8125rem',
    },
    label: {
        flex: 1,
        color: '#374151',
    },
    weight: {
        color: '#ef4444',
        fontWeight: '600',
        fontSize: '0.75rem',
    },
    jumpLink: {
        color: '#3b82f6',
        textDecoration: 'none',
        fontSize: '0.75rem',
        whiteSpace: 'nowrap',
    },
};
