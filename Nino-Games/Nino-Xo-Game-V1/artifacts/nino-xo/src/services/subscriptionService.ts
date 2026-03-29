export type SubscriptionPlan = 'premium' | 'free';

export interface EntitlementResult {
  hasAccess: boolean;
  plan: SubscriptionPlan;
  expiresAt?: string;
}

class SubscriptionService {
  private _cache: Map<string, EntitlementResult> = new Map();
  private _overrideAccess: boolean | null = null;

  setAccessOverride(hasAccess: boolean | null): void {
    this._overrideAccess = hasAccess;
    this._cache.clear();
  }

  async checkEntitlement(userId: string): Promise<EntitlementResult> {
    if (this._overrideAccess !== null) {
      return {
        hasAccess: this._overrideAccess,
        plan: this._overrideAccess ? 'premium' : 'free',
      };
    }

    if (this._cache.has(userId)) {
      return this._cache.get(userId)!;
    }

    await new Promise(r => setTimeout(r, 150));
    const result: EntitlementResult = {
      hasAccess: false,
      plan: 'free',
    };
    this._cache.set(userId, result);
    return result;
  }

  clearCache(): void {
    this._cache.clear();
  }

  async restore(): Promise<EntitlementResult> {
    await new Promise(r => setTimeout(r, 500));
    const result: EntitlementResult = { hasAccess: true, plan: 'premium' };
    return result;
  }
}

export const subscriptionService = new SubscriptionService();
