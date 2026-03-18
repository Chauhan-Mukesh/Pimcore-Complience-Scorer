import React from 'react';
import type { ProfileScore } from './api';

interface ProfileSelectorProps {
    profiles: ProfileScore[];
    selectedProfileId: string | null;
    onSelect: (profileId: string) => void;
}

/**
 * Dropdown to switch between Readiness Profiles.
 * Persists the last selected profile in localStorage.
 */
export const ProfileSelector: React.FC<ProfileSelectorProps> = ({
    profiles,
    selectedProfileId,
    onSelect,
}) => {
    if (profiles.length <= 1) {
        return null;
    }

    return (
        <div style={styles.container}>
            <label htmlFor="readiness-profile-select" style={styles.label}>
                Profile:
            </label>
            <select
                id="readiness-profile-select"
                value={selectedProfileId ?? ''}
                onChange={(e) => onSelect(e.target.value)}
                style={styles.select}
            >
                {profiles.map((p) => (
                    <option key={p.profileId} value={p.profileId}>
                        {p.profileName}
                    </option>
                ))}
            </select>
        </div>
    );
};

const styles: Record<string, React.CSSProperties> = {
    container: {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        marginBottom: '12px',
    },
    label: {
        fontSize: '0.8125rem',
        color: '#6b7280',
        whiteSpace: 'nowrap',
    },
    select: {
        flex: 1,
        padding: '4px 8px',
        borderRadius: '4px',
        border: '1px solid #d1d5db',
        fontSize: '0.8125rem',
        backgroundColor: '#fff',
        cursor: 'pointer',
    },
};
