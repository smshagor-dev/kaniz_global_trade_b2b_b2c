@extends('b2b.layouts.supplier')

@section('panel_content')
    @include('b2b.negotiations._board', ['portal' => 'supplier', 'initialNegotiationId' => $negotiation->id])
@endsection
