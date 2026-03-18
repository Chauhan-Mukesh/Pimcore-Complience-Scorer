import React from 'react';
import type { MissingField } from './api';

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
