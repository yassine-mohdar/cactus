import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../core/theme.dart';
import '../data/friends.dart';
import '../models/friend.dart';
import '../widgets/bottom_nav.dart';
import '../widgets/journey_card.dart';

class MyAdoptionsScreen extends StatelessWidget {
  const MyAdoptionsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final List<Map<String, dynamic>> adoptions = <Map<String, dynamic>>[
      <String, dynamic>{
        'friend': friendsData[0],
        'step': JourneyStep.onTheWay,
        'date': 'Mar 18, 2026',
      },
      <String, dynamic>{
        'friend': friendsData[3],
        'step': JourneyStep.arrived,
        'date': 'Mar 10, 2026',
      },
    ];

    return Scaffold(
      backgroundColor: NinoTheme.background,
      body: ListView(
        padding: EdgeInsets.fromLTRB(
          24,
          MediaQuery.paddingOf(context).top + 12,
          24,
          24,
        ),
        children: <Widget>[
          Text(
            'My Adoptions 📦',
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          const SizedBox(height: 4),
          Text(
            "Track your friends' journeys",
            style: NinoTheme.nunito(
              size: 14,
              weight: FontWeight.w600,
              color: NinoTheme.textMuted,
            ),
          ),
          const SizedBox(height: 24),
          ...adoptions.map((Map<String, dynamic> adoption) {
            final Friend friend = adoption['friend'] as Friend;
            final JourneyStep step = adoption['step'] as JourneyStep;
            final String date = adoption['date'] as String;
            return Padding(
              padding: const EdgeInsets.only(bottom: 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  JourneyCard(
                    friendName: friend.name,
                    friendImage: friend.image,
                    currentStep: step,
                  ),
                  Padding(
                    padding: const EdgeInsets.only(top: 8, left: 4, right: 4),
                    child: Row(
                      children: <Widget>[
                        Text(
                          'Adopted $date',
                          style: NinoTheme.nunito(
                            size: 12,
                            weight: FontWeight.w700,
                            color: NinoTheme.textMuted,
                          ),
                        ),
                        const Spacer(),
                        if (step == JourneyStep.arrived)
                          GestureDetector(
                            onTap: () => context.push('/scan-qr'),
                            child: Text(
                              'Scan QR to activate →',
                              style: NinoTheme.nunito(
                                size: 12,
                                weight: FontWeight.w800,
                                color: NinoTheme.sageDeep,
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
      bottomNavigationBar: const BottomNav(currentPath: '/my-adoptions'),
    );
  }
}
