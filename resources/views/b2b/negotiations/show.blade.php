@extends('b2b.layouts.app')

@section('panel_content')
    @include('b2b.negotiations._board', ['portal' => 'buyer', 'initialNegotiationId' => $negotiation->id])
@endsection
