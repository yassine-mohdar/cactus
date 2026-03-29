import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../pages/account_settings_screen.dart';
import '../pages/address_form_screen.dart';
import '../pages/address_list_screen.dart';
import '../pages/adopt_screen.dart';
import '../pages/adoption_bag_screen.dart';
import '../pages/adoption_success_screen.dart';
import '../pages/community_screen.dart';
import '../pages/complete_adoption_screen.dart';
import '../pages/dm_chat_screen.dart';
import '../pages/dm_inbox_screen.dart';
import '../pages/edit_profile_screen.dart';
import '../pages/entry_screen.dart';
import '../pages/explore_screen.dart';
import '../pages/follow_list_screen.dart';
import '../pages/friend_detail_screen.dart';
import '../pages/friend_profile_screen.dart';
import '../pages/help_support_screen.dart';
import '../pages/home_screen.dart';
import '../pages/login_screen.dart';
import '../pages/my_adoptions_screen.dart';
import '../pages/my_friends_screen.dart';
import '../pages/not_found_screen.dart';
import '../pages/notification_settings_screen.dart';
import '../pages/onboarding_screen.dart';
import '../pages/post_detail_screen.dart';
import '../pages/profile_screen.dart';
import '../pages/reels_viewer_screen.dart';
import '../pages/scan_qr_screen.dart';
import '../pages/settings_screen.dart';
import '../pages/splash_screen.dart';
import '../pages/unified_create_screen.dart';
import '../pages/user_profile_screen.dart';
import '../pages/welcome_home_screen.dart';
import '../widgets/bottom_nav.dart';
import 'theme.dart';

class NinoRouter {
  static final GlobalKey<NavigatorState> _rootNavigatorKey =
      GlobalKey<NavigatorState>();
  static final GlobalKey<NavigatorState> _homeNavigatorKey =
      GlobalKey<NavigatorState>(debugLabel: 'home');
  static final GlobalKey<NavigatorState> _myFriendsNavigatorKey =
      GlobalKey<NavigatorState>(debugLabel: 'my-friends');
  static final GlobalKey<NavigatorState> _communityNavigatorKey =
      GlobalKey<NavigatorState>(debugLabel: 'community');
  static final GlobalKey<NavigatorState> _adoptNavigatorKey =
      GlobalKey<NavigatorState>(debugLabel: 'adopt');
  static final GlobalKey<NavigatorState> _profileNavigatorKey =
      GlobalKey<NavigatorState>(debugLabel: 'profile');

  static ValueKey<String> _pageKey(GoRouterState state) {
    return ValueKey<String>(state.uri.toString());
  }

  static final GoRouter router = GoRouter(
    navigatorKey: _rootNavigatorKey,
    initialLocation: '/',
    errorPageBuilder: (BuildContext context, GoRouterState state) {
      return NinoPageTransitions.cupertino(
        key: _pageKey(state),
        child: NotFoundScreen(path: state.uri.toString()),
      );
    },
    routes: <RouteBase>[
      GoRoute(
        path: '/',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const SplashScreen(),
            ),
      ),
      GoRoute(
        path: '/onboarding',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const OnboardingScreen(),
            ),
      ),
      GoRoute(
        path: '/entry',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const EntryScreen(),
            ),
      ),
      GoRoute(
        path: '/login',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const LoginScreen(),
            ),
      ),
      GoRoute(path: '/create-post', redirect: (_, state) => '/create'),
      GoRoute(path: '/bag', redirect: (_, state) => '/adoption-bag'),
      GoRoute(
        path: '/welcome/:id',
        redirect: (_, GoRouterState state) =>
            '/welcome-home/${state.pathParameters['id']}',
      ),
      StatefulShellRoute.indexedStack(
        builder:
            (
              BuildContext context,
              GoRouterState state,
              StatefulNavigationShell navigationShell,
            ) {
              return _ShellScaffold(navigationShell: navigationShell);
            },
        branches: <StatefulShellBranch>[
          StatefulShellBranch(
            navigatorKey: _homeNavigatorKey,
            routes: <RouteBase>[
              GoRoute(
                path: '/home',
                pageBuilder: (BuildContext context, GoRouterState state) =>
                    NoTransitionPage<void>(
                      key: _pageKey(state),
                      child: const HomeScreen(),
                    ),
              ),
            ],
          ),
          StatefulShellBranch(
            navigatorKey: _myFriendsNavigatorKey,
            routes: <RouteBase>[
              GoRoute(
                path: '/my-friends',
                pageBuilder: (BuildContext context, GoRouterState state) =>
                    NoTransitionPage<void>(
                      key: _pageKey(state),
                      child: const MyFriendsScreen(),
                    ),
                routes: <RouteBase>[
                  GoRoute(
                    path: ':friendId',
                    pageBuilder: (BuildContext context, GoRouterState state) {
                      return NinoPageTransitions.cupertino(
                        key: _pageKey(state),
                        child: FriendProfileScreen(
                          friendId: state.pathParameters['friendId']!,
                          initialTab: _friendProfileTabFromQuery(
                            state.uri.queryParameters['tab'],
                          ),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            navigatorKey: _communityNavigatorKey,
            routes: <RouteBase>[
              GoRoute(
                path: '/community',
                pageBuilder: (BuildContext context, GoRouterState state) =>
                    NoTransitionPage<void>(
                      key: _pageKey(state),
                      child: const CommunityScreen(),
                    ),
              ),
            ],
          ),
          StatefulShellBranch(
            navigatorKey: _adoptNavigatorKey,
            routes: <RouteBase>[
              GoRoute(
                path: '/adopt',
                pageBuilder: (BuildContext context, GoRouterState state) =>
                    NoTransitionPage<void>(
                      key: _pageKey(state),
                      child: const AdoptScreen(),
                    ),
                routes: <RouteBase>[
                  GoRoute(
                    path: ':friendId',
                    pageBuilder: (BuildContext context, GoRouterState state) {
                      return NinoPageTransitions.cupertino(
                        key: _pageKey(state),
                        child: FriendDetailScreen(
                          friendId: state.pathParameters['friendId']!,
                          heroTag: state.extra as String?,
                        ),
                      );
                    },
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            navigatorKey: _profileNavigatorKey,
            routes: <RouteBase>[
              GoRoute(
                path: '/profile',
                pageBuilder: (BuildContext context, GoRouterState state) =>
                    NoTransitionPage<void>(
                      key: _pageKey(state),
                      child: const ProfileScreen(
                        username: 'ninolover_23',
                        isCurrentUser: true,
                      ),
                    ),
                routes: <RouteBase>[
                  GoRoute(
                    path: ':username',
                    pageBuilder: (BuildContext context, GoRouterState state) {
                      return NinoPageTransitions.cupertino(
                        key: _pageKey(state),
                        child: UserProfileScreen(
                          username: state.pathParameters['username']!,
                        ),
                      );
                    },
                  ),
                  GoRoute(
                    path: ':username/followers',
                    pageBuilder: (BuildContext context, GoRouterState state) {
                      return NinoPageTransitions.cupertino(
                        key: _pageKey(state),
                        child: FollowListScreen(
                          username: state.pathParameters['username']!,
                          type: 'followers',
                        ),
                      );
                    },
                  ),
                  GoRoute(
                    path: ':username/following',
                    pageBuilder: (BuildContext context, GoRouterState state) {
                      return NinoPageTransitions.cupertino(
                        key: _pageKey(state),
                        child: FollowListScreen(
                          username: state.pathParameters['username']!,
                          type: 'following',
                        ),
                      );
                    },
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/explore',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const ExploreScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/reels',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const ReelsViewerScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/reels/:reelId',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: ReelsViewerScreen(
                initialReelId: state.pathParameters['reelId'],
              ),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/post/:postId',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: PostDetailScreen(postId: state.pathParameters['postId']!),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/create',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: UnifiedCreateScreen(
                initialMode: _createModeFromQuery(
                  state.uri.queryParameters['mode'],
                ),
              ),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/dm',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const DMInboxScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/dm/:id',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: DMChatScreen(chatId: state.pathParameters['id']!),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/settings',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const SettingsScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/edit-profile',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const EditProfileScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/account-settings',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const AccountSettingsScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/notification-settings',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const NotificationSettingsScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/help-support',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const HelpSupportScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/addresses',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const AddressListScreen(),
            ),
        routes: <RouteBase>[
          GoRoute(
            path: 'new',
            pageBuilder: (BuildContext context, GoRouterState state) =>
                NinoPageTransitions.cupertino(
                  key: _pageKey(state),
                  child: const AddressFormScreen(),
                ),
          ),
          GoRoute(
            path: 'edit/:id',
            pageBuilder: (BuildContext context, GoRouterState state) =>
                NinoPageTransitions.cupertino(
                  key: _pageKey(state),
                  child: AddressFormScreen(
                    addressId: state.pathParameters['id'],
                  ),
                ),
          ),
        ],
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/adoption-bag',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const AdoptionBagScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/complete-adoption',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const CompleteAdoptionScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/adoption-success/:friendId',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: AdoptionSuccessScreen(
                friendId: state.pathParameters['friendId']!,
              ),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/my-adoptions',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const MyAdoptionsScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/scan-qr',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: const ScanQRScreen(),
            ),
      ),
      GoRoute(
        parentNavigatorKey: _rootNavigatorKey,
        path: '/welcome-home/:friendId',
        pageBuilder: (BuildContext context, GoRouterState state) =>
            NinoPageTransitions.cupertino(
              key: _pageKey(state),
              child: WelcomeHomeScreen(
                friendId: state.pathParameters['friendId']!,
              ),
            ),
      ),
    ],
  );

  static FriendProfileTab _friendProfileTabFromQuery(String? raw) {
    return switch (raw) {
      'care' => FriendProfileTab.care,
      'journal' => FriendProfileTab.journal,
      'chat' => FriendProfileTab.chat,
      _ => FriendProfileTab.overview,
    };
  }

  static CreateMode _createModeFromQuery(String? raw) {
    return switch (raw) {
      'story' => CreateMode.story,
      'reel' => CreateMode.reel,
      _ => CreateMode.post,
    };
  }
}

class _ShellScaffold extends StatelessWidget {
  const _ShellScaffold({required this.navigationShell});

  final StatefulNavigationShell navigationShell;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: navigationShell,
      bottomNavigationBar: BottomNav(navigationShell: navigationShell),
    );
  }
}
