import { useQuery } from '@tanstack/react-query';
import { useAuthStore } from '../stores/authStore';
import { subscriptionService, EntitlementResult } from '../services/subscriptionService';

export type { EntitlementResult };

export function useEntitlement() {
  const profile = useAuthStore(s => s.profile);

  const { data, isLoading } = useQuery<EntitlementResult>({
    queryKey: ['entitlement', profile?.id ?? 'anonymous'],
    queryFn: () => subscriptionService.checkEntitlement(profile?.id ?? 'anonymous'),
    staleTime: 5 * 60 * 1000,
    enabled: !!profile,
  });

  const hasAccess = data?.hasAccess || profile?.hasSubscription || false;

  return {
    hasAccess,
    isLoading,
    plan: data?.plan ?? (hasAccess ? 'premium' : 'free'),
    expiresAt: data?.expiresAt,
  };
}
