/**
 * Entry point for the Market Readiness Shield Studio widget.
 *
 * This file registers the ReadinessPanel as a Pimcore Studio sidebar widget.
 * The registration hook depends on the Pimcore Studio UI plugin API which
 * is injected at runtime by the host application.
 */

export { ReadinessPanel } from './ReadinessPanel';
export { ScoreRing } from './ScoreRing';
export { MissingFieldList } from './MissingFieldList';
export { ProfileSelector } from './ProfileSelector';
export type { MissingField, ProfileScore, ScoreResponse } from './api';
