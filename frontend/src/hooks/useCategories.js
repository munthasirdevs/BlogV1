import { useQuery } from '@tanstack/react-query';
import { QUERY_KEYS } from '@/constants';
import { categoryService } from '@/services';

/**
 * Hook to fetch all categories
 * @param {Object} params - Query parameters
 */
export function useCategories(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.CATEGORIES.ALL, params],
    queryFn: () => categoryService.getAll(params),
    staleTime: 10 * 60 * 1000, // 10 minutes
  });
}

/**
 * Hook to fetch a single category by slug
 * @param {string} slug - Category slug
 */
export function useCategoryBySlug(slug) {
  return useQuery({
    queryKey: [QUERY_KEYS.CATEGORIES.DETAIL, slug],
    queryFn: () => categoryService.getBySlug(slug),
    enabled: !!slug,
    staleTime: 10 * 60 * 1000,
  });
}

/**
 * Hook to fetch posts by category
 * @param {string} slug - Category slug
 * @param {Object} params - Query parameters
 */
export function usePostsByCategory(slug, params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.CATEGORIES.DETAIL, slug, 'posts', params],
    queryFn: async () => {
      const category = await categoryService.getBySlug(slug);
      const posts = await categoryService.getPosts(category.data.id, params);
      return { category: category.data, posts: posts.data, meta: posts.meta };
    },
    enabled: !!slug,
    staleTime: 5 * 60 * 1000,
  });
}

export default useCategories;
