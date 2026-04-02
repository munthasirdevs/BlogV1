import { useQuery } from '@tanstack/react-query';
import { analyticsService } from '@/services';
import { QUERY_KEYS } from '@/constants';

/**
 * Hook to get dashboard statistics
 */
export function useDashboardStats() {
  return useQuery({
    queryKey: [QUERY_KEYS.ANALYTICS?.STATS || 'analytics-stats'],
    queryFn: analyticsService.getStats,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

/**
 * Hook to get views over time data
 * @param {Object} params - Query parameters
 */
export function useViewsOverTime(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.ANALYTICS?.VIEWS || 'analytics-views', params],
    queryFn: () => analyticsService.getViewsOverTime(params),
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to get engagement over time data
 * @param {Object} params - Query parameters
 */
export function useEngagementOverTime(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.ANALYTICS?.ENGAGEMENT || 'analytics-engagement', params],
    queryFn: () => analyticsService.getEngagementOverTime(params),
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to get top posts
 * @param {Object} params - Query parameters
 */
export function useTopPosts(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.ANALYTICS?.TOP_POSTS || 'analytics-top-posts', params],
    queryFn: () => analyticsService.getTopPosts(params),
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to get traffic sources
 * @param {Object} params - Query parameters
 */
export function useTrafficSources(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.ANALYTICS?.TRAFFIC || 'analytics-traffic', params],
    queryFn: () => analyticsService.getTrafficSources(params),
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to get device breakdown
 * @param {Object} params - Query parameters
 */
export function useDeviceBreakdown(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.ANALYTICS?.DEVICES || 'analytics-devices', params],
    queryFn: () => analyticsService.getDeviceBreakdown(params),
    staleTime: 5 * 60 * 1000,
  });
}

export default {
  useDashboardStats,
  useViewsOverTime,
  useEngagementOverTime,
  useTopPosts,
  useTrafficSources,
  useDeviceBreakdown,
};
