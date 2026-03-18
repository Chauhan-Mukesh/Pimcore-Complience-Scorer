import React, { useCallback, useEffect, useRef, useState } from 'react';
import type { ProfileScore, ScoreResponse } from './api';
import { fetchScore, triggerRecalculate } from './api';
import { MissingFieldList } from './MissingFieldList';
import { ProfileSelector } from './ProfileSelector';
import { ScoreRing } from './ScoreRing';

const STORAGE_KEY_PREFIX = 'readiness_panel_profile_';
const POLL_INTERVAL_MS = 5_000;

interface ReadinessPanelProps {
    /** Pimcore DataObject ID. */
    objectId: number;
}

/**
 * Main sidebar widget panel for the Market Readiness Shield.
 *
 * - Fetches the score on mount.
 * - Polls every 5 s while status === 'pending'.
 * - Allows selecting between profiles.
 * - Shows a recalculate button.
 */
export const ReadinessPanel: React.FC<ReadinessPanelProps> = ({ objectId }) => {
    const [scoreData, setScoreData] = useState<ScoreResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [recalculating, setRecalculating] = useState(false);
    const [selectedProfileId, setSelectedProfileId] = useState<string | null>(
        () => localStorage.getItem(`${STORAGE_KEY_PREFIX}${objectId}`),
    );

    const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const loadScore = useCallback(async () => {
        try {
            const data = await fetchScore(objectId);
            setScoreData(data);
            setError(null);

            // Auto-select first profile if none is selected or stored.
            if (data.profiles.length > 0) {
                const stored = localStorage.getItem(`${STORAGE_KEY_PREFIX}${objectId}`);
                const validStored = data.profiles.find((p) => p.profileId === stored);
                if (!validStored) {
                    const firstId = data.profiles[0].profileId;
                    setSelectedProfileId(firstId);
                    localStorage.setItem(`${STORAGE_KEY_PREFIX}${objectId}`, firstId);
                }
            }

            // Stop polling when status is ready.
            if (data.status === 'ready' && pollRef.current !== null) {
                clearInterval(pollRef.current);
                pollRef.current = null;
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Unknown error');
        } finally {
            setLoading(false);
        }
    }, [objectId]);

    useEffect(() => {
        void loadScore();

        // Start polling if we don't yet have a ready score.
        pollRef.current = setInterval(() => {
            void loadScore();
        }, POLL_INTERVAL_MS);

        return () => {
            if (pollRef.current !== null) {
                clearInterval(pollRef.current);
            }
        };
    }, [loadScore]);

    const handleProfileSelect = (profileId: string) => {
        setSelectedProfileId(profileId);
        localStorage.setItem(`${STORAGE_KEY_PREFIX}${objectId}`, profileId);
    };

    const handleRecalculate = async () => {
        setRecalculating(true);
        try {
            await triggerRecalculate(objectId);
            // Wait a beat, then reload.
            setTimeout(() => {
                void loadScore();
                setRecalculating(false);
            }, 1_500);
        } catch (err) {
            console.error('[ReadinessShield] Recalculation failed:', err);
            setError(err instanceof Error ? err.message : 'Recalculation failed');
            setRecalculating(false);
        }
    };

    const activeProfile: ProfileScore | null =
        scoreData?.profiles.find((p) => p.profileId === selectedProfileId) ??
        scoreData?.profiles[0] ??
        null;

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    if (loading) {
        return <div style={styles.panel}><p style={styles.muted}>Calculating readiness…</p></div>;
    }

    if (error !== null) {
        return (
            <div style={styles.panel}>
                <p style={styles.errorText}>⚠ {error}</p>
                <button onClick={() => void loadScore()} style={styles.button}>
                    Retry
                </button>
            </div>
        );
    }

    if (scoreData === null || scoreData.status === 'pending' || activeProfile === null) {
        return (
            <div style={styles.panel}>
                <p style={styles.muted}>⏳ Score calculation in progress…</p>
            </div>
        );
    }

    return (
        <div style={styles.panel}>
            <div style={styles.header}>
                <span style={styles.title}>Market Readiness</span>
                <button
                    onClick={() => void handleRecalculate()}
                    disabled={recalculating}
                    style={styles.refreshButton}
                    title="Recalculate score"
                >
                    {recalculating ? '⟳' : '↻'}
                </button>
            </div>

            <ProfileSelector
                profiles={scoreData.profiles}
                selectedProfileId={selectedProfileId}
                onSelect={handleProfileSelect}
            />

            <div style={styles.scoreRow}>
                <ScoreRing score={activeProfile.score} size={80} />
                <div style={styles.scoreSummary}>
                    <span style={styles.profileName}>{activeProfile.profileName}</span>
                    <span style={styles.scoreValue}>{activeProfile.score.toFixed(1)}%</span>
                    <span style={styles.scoreLabel}>Compliance</span>
                    <span style={styles.calculatedAt}>
                        Updated: {new Date(activeProfile.calculatedAt).toLocaleTimeString()}
                    </span>
                </div>
            </div>

            <div style={styles.divider} />

            <div style={styles.missingSection}>
                <h3 style={styles.sectionTitle}>
                    Missing ({activeProfile.missingFields.length})
                </h3>
                <MissingFieldList
                    missingFields={activeProfile.missingFields}
                    objectId={objectId}
                />
            </div>
        </div>
    );
};

const styles: Record<string, React.CSSProperties> = {
    panel: {
        padding: '12px 14px',
        fontFamily: 'system-ui, -apple-system, sans-serif',
        fontSize: '0.875rem',
        color: '#111827',
        backgroundColor: '#fff',
        borderLeft: '3px solid #3b82f6',
    },
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '12px',
    },
    title: {
        fontWeight: '700',
        fontSize: '0.9375rem',
        color: '#1d4ed8',
    },
    refreshButton: {
        background: 'none',
        border: 'none',
        cursor: 'pointer',
        fontSize: '1.1rem',
        color: '#6b7280',
        padding: '2px 4px',
        lineHeight: 1,
    },
    scoreRow: {
        display: 'flex',
        alignItems: 'center',
        gap: '16px',
        marginBottom: '14px',
    },
    scoreSummary: {
        display: 'flex',
        flexDirection: 'column',
        gap: '2px',
    },
    profileName: {
        fontSize: '0.8125rem',
        color: '#374151',
        fontWeight: '600',
    },
    scoreValue: {
        fontSize: '1.5rem',
        fontWeight: '800',
        color: '#111827',
        lineHeight: 1,
    },
    scoreLabel: {
        fontSize: '0.75rem',
        color: '#6b7280',
    },
    calculatedAt: {
        fontSize: '0.6875rem',
        color: '#9ca3af',
    },
    divider: {
        borderTop: '1px solid #e5e7eb',
        margin: '10px 0',
    },
    missingSection: {},
    sectionTitle: {
        fontSize: '0.8125rem',
        fontWeight: '600',
        color: '#374151',
        margin: '0 0 8px',
    },
    muted: {
        color: '#9ca3af',
        fontSize: '0.8125rem',
    },
    errorText: {
        color: '#ef4444',
        fontSize: '0.8125rem',
    },
    button: {
        marginTop: '8px',
        padding: '4px 12px',
        borderRadius: '4px',
        border: '1px solid #d1d5db',
        background: '#f9fafb',
        cursor: 'pointer',
        fontSize: '0.8125rem',
    },
};
