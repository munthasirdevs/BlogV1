import { useQuery } from '@tanstack/react-query';
import { QUERY_KEYS } from '@/constants';
import { tagService } from '@/services';

/**
 * Hook to fetch all tags
 * @param {Object} params - Query parameters
 */
export function useTags(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.TAGS.ALL, params],
    queryFn: () => tagService.getAll(params),
    staleTime: 10 * 60 * 1000, // 10 minutes
  });
}

/**
 * Hook to fetch a single tag by slug
 * @param {string} slug - Tag slug
 */
export function useTagBySlug(slug) {
  return useQuery({
    queryKey: [QUERY_KEYS.TAGS.DETAIL, slug],
    queryFn: () => tagService.getBySlug(slug),
    enabled: !!slug,
    staleTime: 10 * 60 * 1000,
  });
}

/**
 * Hook to fetch posts by tag
 * @param {string} slug - Tag slug
 * @param {Object} params - Query parameters
 */
export function usePostsByTag(slug, params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.TAGS.DETAIL, slug, 'posts', params],
    queryFn: async () => {
      const tag = await tagService.getBySlug(slug);
      const posts = await tagService.getPosts(tag.data.id, params);
      return { tag: tag.data, posts: posts.data, meta: posts.meta };
    },
    enabled: !!slug,
    staleTime: 5 * 60 * 1000,
  });
}

export default useTags;
