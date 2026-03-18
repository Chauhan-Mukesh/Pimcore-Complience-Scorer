import React from 'react';
import type { QualityDimension } from './api';

const DIMENSION_META: Record<QualityDimension, { icon: string; label: string }> = {
    completeness: { icon: '📋', label: 'Completeness' },
    consistency:  { icon: '🔗', label: 'Consistency' },
    accuracy:     { icon: '🎯', label: 'Accuracy' },
    format:       { icon: '🔤', label: 'Format' },
    uniqueness:   { icon: '✨', label: 'Uniqueness' },
    conformity:   { icon: '📚', label: 'Conformity' },
    timeliness:   { icon: '⏱', label: 'Timeliness' },
};

interface DimensionBreakdownProps {
    dimensionScores: Record<QualityDimension, number>;
}

/**
 * Renders a compact progress-bar breakdown of scores per quality dimension.
 * Only shows dimensions that have less than 100% (i.e. there are violations).
 */
export const DimensionBreakdown: React.FC<DimensionBreakdownProps> = ({ dimensionScores }) => {
    const entries = (Object.entries(dimensionScores) as [QualityDimension, number][])
        .filter(([, score]) => score < 100)
        .sort(([, a], [, b]) => a - b); // worst first

    if (entries.length === 0) {
        return null;
    }

    return (
        <div style={styles.container}>
            <h3 style={styles.title}>Dimension Breakdown</h3>
            {entries.map(([dim, score]) => {
                const meta = DIMENSION_META[dim] ?? { icon: '▪', label: dim };
                const colour = score >= 80 ? '#22c55e' : score >= 50 ? '#f59e0b' : '#ef4444';
                return (
                    <div key={dim} style={styles.row}>
                        <span style={styles.dimLabel} title={meta.label}>
                            {meta.icon} {meta.label}
                        </span>
                        <div style={styles.barTrack}>
                            <div
                                style={{
                                    ...styles.barFill,
                                    width: `${score}%`,
                                    backgroundColor: colour,
                                }}
                            />
                        </div>
                        <span style={{ ...styles.score, color: colour }}>
                            {score.toFixed(0)}%
                        </span>
                    </div>
                );
            })}
        </div>
    );
};

const styles: Record<string, React.CSSProperties> = {
    container: {
        marginBottom: '10px',
    },
    title: {
        fontSize: '0.8125rem',
        fontWeight: '600',
        color: '#374151',
        margin: '0 0 8px',
    },
    row: {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        marginBottom: '5px',
    },
    dimLabel: {
        width: '110px',
        fontSize: '0.75rem',
        color: '#4b5563',
        flexShrink: 0,
        whiteSpace: 'nowrap',
        overflow: 'hidden',
        textOverflow: 'ellipsis',
    },
    barTrack: {
        flex: 1,
        height: '6px',
        backgroundColor: '#e5e7eb',
        borderRadius: '3px',
        overflow: 'hidden',
    },
    barFill: {
        height: '100%',
        borderRadius: '3px',
        transition: 'width 0.3s ease',
    },
    score: {
        width: '34px',
        textAlign: 'right',
        fontSize: '0.75rem',
        fontWeight: '600',
        flexShrink: 0,
    },
};
