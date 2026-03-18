/**
 * API client for the Market Readiness Shield REST endpoints.
 * Called from the Studio sidebar widget.
 */

export type SeverityLevel = 'error' | 'warning' | 'info';
export type QualityDimension =
    | 'completeness'
    | 'consistency'
    | 'accuracy'
    | 'format'
    | 'uniqueness'
    | 'conformity'
    | 'timeliness';

export interface MissingField {
    fieldPath: string;
    label: string;
    weight: number;
    tabHint: string | null;
    severity: SeverityLevel;
    dimension: QualityDimension;
    errorMessage: string | null;
}

export interface ProfileScore {
    profileId: string;
    profileName: string;
    score: number;
    missingFields: MissingField[];
    /** Per-dimension sub-scores (0–100). */
    dimensionScores: Record<QualityDimension, number>;
    /** Violation counts per severity level. */
    severityCounts: Record<SeverityLevel, number>;
    calculatedAt: string;
}

export interface ScoreResponse {
    objectId: number;
    status: 'ready' | 'pending';
    profiles: ProfileScore[];
}

const BASE_URL = '/api/readiness';

/**
 * Fetches the current readiness scores for a DataObject.
 */
export async function fetchScore(objectId: number): Promise<ScoreResponse> {
    const response = await fetch(`${BASE_URL}/score/${objectId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include',
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch readiness score: HTTP ${response.status}`);
    }

    return response.json() as Promise<ScoreResponse>;
}

/**
 * Triggers an asynchronous recalculation for the given object.
 * Returns a 202 Accepted response immediately.
 */
export async function triggerRecalculate(objectId: number): Promise<void> {
    const response = await fetch(`${BASE_URL}/score/${objectId}/recalculate`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include',
    });

    if (!response.ok) {
        throw new Error(`Failed to trigger recalculation: HTTP ${response.status}`);
    }
}


/**
 * Fetches the current readiness scores for a DataObject.
 */
export async function fetchScore(objectId: number): Promise<ScoreResponse> {
    const response = await fetch(`${BASE_URL}/score/${objectId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include',
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch readiness score: HTTP ${response.status}`);
    }

    return response.json() as Promise<ScoreResponse>;
}

/**
 * Triggers an asynchronous recalculation for the given object.
 * Returns a 202 Accepted response immediately.
 */
export async function triggerRecalculate(objectId: number): Promise<void> {
    const response = await fetch(`${BASE_URL}/score/${objectId}/recalculate`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include',
    });

    if (!response.ok) {
        throw new Error(`Failed to trigger recalculation: HTTP ${response.status}`);
    }
}
