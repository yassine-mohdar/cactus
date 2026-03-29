import { Redirect, useLocalSearchParams } from 'expo-router';
import React from 'react';

export default function JoinRoomRedirect() {
  const { code } = useLocalSearchParams<{ code: string }>();
  return <Redirect href={`/room/${code}` as never} />;
}
