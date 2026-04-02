import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { QUERY_KEYS } from '@/constants';
import { userService } from '@/services';

/**
 * Hook to fetch all users
 */
export function useUsers(params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.USERS.ALL, params],
    queryFn: () => userService.getAll(params),
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to fetch user profile by username
 */
export function useUserProfile(username) {
  return useQuery({
    queryKey: [QUERY_KEYS.USERS.PROFILE, username],
    queryFn: () => userService.getProfile(username),
    enabled: !!username,
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to update user profile
 */
export function useUpdateProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (userData) => userService.updateProfile(userData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.AUTH.ME] });
    },
  });
}

/**
 * Hook to update user password
 */
export function useUpdatePassword() {
  return useMutation({
    mutationFn: (passwordData) => userService.updatePassword(passwordData),
  });
}

/**
 * Hook to follow a user
 */
export function useFollowUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (userId) => userService.follow(userId),
    onSuccess: (_, userId) => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.USERS.PROFILE] });
    },
  });
}

/**
 * Hook to unfollow a user
 */
export function useUnfollowUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (userId) => userService.unfollow(userId),
    onSuccess: (_, userId) => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.USERS.PROFILE] });
    },
  });
}

/**
 * Hook to fetch author by ID or slug
 * @param {string} identifier - Author ID or slug
 */
export function useAuthor(identifier) {
  return useQuery({
    queryKey: [QUERY_KEYS.USERS.PROFILE, 'author', identifier],
    queryFn: () => {
      // Try to fetch by slug first (username)
      return userService.getProfile(identifier);
    },
    enabled: !!identifier,
    staleTime: 5 * 60 * 1000,
  });
}

/**
 * Hook to fetch author's posts
 * @param {string} authorId - Author ID
 * @param {Object} params - Query parameters
 */
export function useAuthorPosts(authorId, params = {}) {
  return useQuery({
    queryKey: [QUERY_KEYS.USERS.PROFILE, authorId, 'posts', params],
    queryFn: () => userService.getPosts(authorId, params),
    enabled: !!authorId,
    staleTime: 5 * 60 * 1000,
  });
}
