import React from 'react';

interface ScoreRingProps {
    /** Score value in [0, 100]. */
    score: number;
    /** Diameter of the ring in pixels. Defaults to 80. */
    size?: number;
}

/**
 * SVG-based circular progress ring.
 *
 * Color thresholds:
 *   - red    → score < 50
 *   - orange → score >= 50 && < 80
 *   - green  → score >= 80
 */
export const ScoreRing: React.FC<ScoreRingProps> = ({ score, size = 80 }) => {
    const radius = (size - 10) / 2;
    const circumference = 2 * Math.PI * radius;
    const clampedScore = Math.min(100, Math.max(0, score));
    const offset = circumference - (clampedScore / 100) * circumference;

    const color =
        clampedScore >= 80
            ? '#22c55e'  // green-500
            : clampedScore >= 50
              ? '#f97316' // orange-500
              : '#ef4444'; // red-500

    return (
        <svg
            width={size}
            height={size}
            viewBox={`0 0 ${size} ${size}`}
            role="img"
            aria-label={`Readiness score: ${clampedScore.toFixed(0)}%`}
        >
            {/* Background track */}
            <circle
                cx={size / 2}
                cy={size / 2}
                r={radius}
                fill="none"
                stroke="#e5e7eb"
                strokeWidth={8}
            />
            {/* Progress arc */}
            <circle
                cx={size / 2}
                cy={size / 2}
                r={radius}
                fill="none"
                stroke={color}
                strokeWidth={8}
                strokeDasharray={circumference}
                strokeDashoffset={offset}
                strokeLinecap="round"
                transform={`rotate(-90 ${size / 2} ${size / 2})`}
                style={{ transition: 'stroke-dashoffset 0.6s ease, stroke 0.6s ease' }}
            />
            {/* Score label */}
            <text
                x="50%"
                y="50%"
                textAnchor="middle"
                dominantBaseline="central"
                fontSize={size * 0.22}
                fontWeight="700"
                fill={color}
            >
                {clampedScore.toFixed(0)}%
            </text>
        </svg>
    );
};
