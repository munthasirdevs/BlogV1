import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { QUERY_KEYS } from '@/constants';
import { postService } from '@/services';

/**
 * Hook to fetch all posts
 */
export function usePosts(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.POSTS.ALL, params],
    queryFn: () => postService.getAll(params),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

/**
 * Hook to fetch a single post by slug
 */
export function usePost(slug) {
  return useQuery({
    queryKey: [QUERY_KEYS.POSTS.SLUG, slug],
    queryFn: () => postService.getBySlug(slug),
    enabled: !!slug,
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to fetch a single post by ID
 */
export function usePostById(id) {
  return useQuery({
    queryKey: [QUERY_KEYS.POSTS.DETAIL, id],
    queryFn: () => postService.getById(id),
    enabled: !!id,
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to create a new post
 */
export function useCreatePost() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (postData) => postService.create(postData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.POSTS.ALL] });
    },
  });
}

/**
 * Hook to update a post
 */
export function useUpdatePost() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, ...postData }) => postService.update(id, postData),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.POSTS.DETAIL, id] });
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.POSTS.ALL] });
    },
  });
}

/**
 * Hook to delete a post
 */
export function useDeletePost() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id) => postService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.POSTS.ALL] });
    },
  });
}

/**
 * Hook to like a post
 */
export function useLikePost() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id) => postService.like(id),
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.POSTS.DETAIL, id] });
    },
  });
}

/**
 * Hook to unlike a post
 */
export function useUnlikePost() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id) => postService.unlike(id),
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.POSTS.DETAIL, id] });
    },
  });
}

/**
 * Hook to fetch featured posts
 * @param {Object} params - Query parameters
 */
export function useFeaturedPosts(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.POSTS.ALL, 'featured', params],
    queryFn: () => postService.getFeatured(params),
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to fetch trending posts
 * @param {Object} params - Query parameters
 */
export function useTrendingPosts(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.POSTS.ALL, 'trending', params],
    queryFn: () => postService.getTrending(params),
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to fetch related posts
 * @param {number|string} postId - Post ID
 * @param {number} limit - Number of posts to return
 */
export function useRelatedPosts(postId, limit = 3) {
  return useQuery({
    queryKey: [QUERY_KEYS.POSTS.ALL, postId, 'related', limit],
    queryFn: () => postService.getRelated(postId, limit),
    enabled: !!postId,
    staleTime: 5 * 60 * 1000,
  });
}
