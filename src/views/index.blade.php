<ul>
@foreach($customers as $customer)
    <li>Id: {{ $customer->id }} - nome: {{ $customer->name }} | email: {{ $customer->email }}</li>
@endforeach
</ul>
